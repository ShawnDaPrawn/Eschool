<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Category;
use App\Models\Mediums;
use App\Models\Platform\PlatformSchoolImport;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\People\SchoolImports;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Onboarding imports run by Navigate Ed Operations on a school's behalf
 * (Action Plan SA.11). Operations choose the school, the kind of import and
 * the school leader who will approve it, and preview the file: the school's
 * own import check (SchoolImports, 0.11) runs in that school as that leader,
 * and nothing is written. Sending it for approval stores the file encrypted
 * with the preview the leader will see. The leader approves or declines it
 * in the school's panel, with a reason. Only then may Operations apply it,
 * through SchoolImports::commit() in the same school as the same leader, so
 * tenancy, validation and permissions are exactly the school's own; the file
 * must still be the one that was previewed and approved (SHA-256), and the
 * school's records must not have changed since (the check's fingerprint).
 *
 * The file and its preview are wiped when the import is applied, declined,
 * cancelled, stopped or expires (EXPIRY_DAYS after it was sent). Every step
 * is a platform_import_* audit entry for the school, which the HQ audit log
 * and the school's own page both show. engineering/specs/platform/hq-imports.md.
 */
class PlatformSchoolImports
{
    /** HQ works in South African time; rows are stored in the app's timezone. */
    public const TIMEZONE = 'Africa/Johannesburg';

    /** Days a sent import waits for approval and applying before its file is deleted. */
    public const EXPIRY_DAYS = 7;

    /** The school leader's notice that an import waits for their approval. */
    public const NOTICE = 'people.hq_import_approval';

    public const KINDS = ['homerooms' => 'Homerooms', 'learners' => 'Learners and guardians', 'staff' => 'Staff'];

    public const STATUSES = [
        PlatformSchoolImport::AWAITING => 'Waiting for the school\'s approval',
        PlatformSchoolImport::APPROVED => 'Approved by the school',
        PlatformSchoolImport::APPLIED => 'Imported',
        PlatformSchoolImport::DECLINED => 'Declined by the school',
        PlatformSchoolImport::CANCELLED => 'Cancelled by Navigate Ed',
        PlatformSchoolImport::VOID => 'Stopped: the file or the school\'s records changed',
        PlatformSchoolImport::EXPIRED => 'Expired',
    ];

    /** Why an import was stopped. */
    public const STOPPED = [
        'file_changed' => 'The stored file is not the one that was previewed and approved.',
        'records_changed' => 'The school\'s records changed since the preview, so the rows would not be the ones approved.',
        'approver_left' => 'The approving leader may no longer import this at the school.',
    ];

    /** Each step, as the school's page and the HQ timeline describe it. */
    public const STEPS = [
        'platform_import_previewed' => 'Navigate Ed checked the file (nothing written)',
        'platform_import_requested' => 'Navigate Ed sent it for approval',
        'platform_import_approved' => 'Approved',
        'platform_import_declined' => 'Declined',
        'platform_import_applied' => 'Imported by Navigate Ed',
        'platform_import_cancelled' => 'Cancelled by Navigate Ed',
        'platform_import_stopped' => 'Stopped',
        'platform_import_expired' => 'Expired, file deleted',
    ];

    public function __construct(
        private readonly CurrentSchool $currentSchool,
        private readonly SchoolImports $imports,
        private readonly PlatformStaffAccess $access,
    ) {}

    /** The preview form: the file comes as csv (the controller reads an upload into it). */
    public static function previewRules(): array
    {
        return [
            'kind' => ['required', Rule::in(array_keys(self::KINDS))],
            'approver_id' => ['required', 'integer', 'min:1'],
            'csv' => ['nullable', 'string', 'max:1100000'],
            'mapped' => ['nullable', 'boolean'],
            'mapping' => ['nullable', 'array', 'max:40'],
            'mapping.*' => ['nullable', 'string', 'max:200'],
            'medium_id' => ['nullable', 'integer', 'min:1'],
            'session_year_id' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'invite' => ['nullable', 'boolean'],
            'from_sasams' => ['nullable', 'boolean'],
        ];
    }

    /** Sending a previewed file for approval: the preview's fingerprint must come back. */
    public static function requestRules(): array
    {
        return ['csv' => ['required', 'string', 'max:1100000'], 'fingerprint' => ['required', 'string', 'size:64']] + self::previewRules();
    }

    public static function reasonRules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:500']];
    }

    /** @return array{id: int, name: string, status: string} the school, or 404 */
    public function school(int $schoolId): array
    {
        $row = $this->currentSchool->acrossSchools(fn () => DB::table('schools')->where('id', $schoolId)->first(['id', 'name', 'status']));
        abort_if($row === null, 404);

        return ['id' => (int) $row->id, 'name' => (string) $row->name, 'status' => (string) $row->status];
    }

    /**
     * The school's staff who may approve an import, with the kinds each may
     * import (the school permission each kind needs). Names only.
     *
     * @return array<int, array{name: string, kinds: list<string>}>
     */
    public function approvers(int $schoolId): array
    {
        return $this->currentSchool->run($schoolId, function () use ($schoolId): array {
            $ids = SchoolMembership::query()->active()->where('school_memberships.school_id', $schoolId)
                ->whereNotIn('school_memberships.role', [SchoolRoles::STUDENT, SchoolRoles::PARENT])->pluck('school_memberships.user_id')->unique();
            $approvers = [];
            foreach (User::query()->whereIn('id', $ids)->where('status', 1)->orderBy('first_name')->orderBy('last_name')->limit(500)->get() as $user) {
                $kinds = array_keys($this->imports->allowed($user));
                if ($kinds !== []) {
                    $approvers[(int) $user->id] = ['name' => trim($user->first_name.' '.$user->last_name), 'kinds' => $kinds];
                }
            }

            return $approvers;
        });
    }

    /**
     * The school's own choices for an import's settings: mediums, school years
     * and learner categories.
     *
     * @return array{mediums: array<int, string>, years: array<int, string>, categories: array<int, string>}
     */
    public function choices(int $schoolId): array
    {
        return $this->currentSchool->run($schoolId, fn (): array => [
            'mediums' => Mediums::query()->orderBy('name')->pluck('name', 'id')->all(),
            'years' => SessionYear::query()->orderByDesc('start_date')->pluck('name', 'id')->all(),
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    /**
     * Checks the file in the school as the approving leader; nothing is
     * written but the audit entry.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed> SchoolImports::check() for the file
     */
    public function preview(User $operator, int $schoolId, array $input): array
    {
        abort_unless($this->access->can($operator, 'imports.run'), 403);
        $data = Validator::make($input, self::previewRules())->validate();
        $this->activeSchool($schoolId);
        $csv = $this->csv($data);

        return $this->currentSchool->run($schoolId, function () use ($operator, $schoolId, $data, $csv): array {
            $approver = $this->approver((int) $data['approver_id'], (string) $data['kind']);
            $check = $this->imports->check($approver, (string) $data['kind'], $csv, $this->mapping($data), $this->options($data));
            Audit::record('platform_import_previewed', null, [], [
                'kind' => $data['kind'], 'file_sha256' => hash('sha256', $csv), 'rows' => count($check['rows']),
                'counts' => $check['counts'], 'approver_id' => (int) $approver->id, 'changed_by' => (int) $operator->id,
            ], $schoolId);

            return $check;
        });
    }

    /**
     * Stores the previewed file, encrypted, for the leader's approval and
     * tells them. Refused unless the check still gives the preview's result.
     *
     * @param  array<string, mixed>  $input
     */
    public function request(User $operator, int $schoolId, array $input): PlatformSchoolImport
    {
        abort_unless($this->access->can($operator, 'imports.run'), 403);
        $data = Validator::make($input, self::requestRules())->validate();
        $this->activeSchool($schoolId);
        $csv = $this->csv($data);

        return $this->currentSchool->run($schoolId, fn (): PlatformSchoolImport => DB::transaction(function () use ($operator, $data, $csv): PlatformSchoolImport {
            $kind = (string) $data['kind'];
            $approver = $this->approver((int) $data['approver_id'], $kind);
            $check = $this->imports->check($approver, $kind, $csv, $this->mapping($data), $this->options($data));
            if (! hash_equals($check['fingerprint'], (string) $data['fingerprint'])) {
                throw ValidationException::withMessages(['fingerprint' => __('The file, its settings or the school\'s records changed since the preview. Check the file again.')]);
            }
            if ($check['missing'] !== [] || $check['counts']['error'] > 0) {
                throw ValidationException::withMessages(['rows' => __('Fix the rows with problems first, or remove them from the file.')]);
            }
            if ($check['counts']['create'] + $check['counts']['lurits'] === 0) {
                throw ValidationException::withMessages(['rows' => __('Nothing in this file would be imported.')]);
            }
            $import = PlatformSchoolImport::query()->create([
                'kind' => $kind, 'status' => PlatformSchoolImport::AWAITING, 'requested_by' => (int) $operator->id, 'approver_id' => (int) $approver->id,
                'contents' => $csv, 'preview' => array_map(fn (array $row) => Arr::except($row, ['data']), $check['rows']),
                'file_sha256' => hash('sha256', $csv), 'fingerprint' => $check['fingerprint'], 'mapping' => $check['mapping'],
                'options' => $check['options'], 'counts' => $check['counts'], 'rows' => count($check['rows']), 'expires_at' => now()->addDays(self::EXPIRY_DAYS),
            ]);
            Audit::record('platform_import_requested', $import, [], [
                'import_id' => (int) $import->id, 'kind' => $kind, 'file_sha256' => $import->file_sha256, 'rows' => $import->rows, 'counts' => $check['counts'],
                'approver_id' => (int) $approver->id, 'expires_at' => $import->expires_at->toIso8601String(), 'changed_by' => (int) $operator->id,
            ]);
            $notifier = app(Notifier::class);
            $notice = $notifier->makeNotice(self::NOTICE, __('Import waiting for your approval'), __('Navigate Ed prepared an import for your school. Check the rows, then approve or decline it.'), '/hq-imports/'.$import->id);
            $notifier->assign($notice, [(int) $approver->id]);

            return $import;
        }));
    }

    /**
     * The approving leader approves the import, in their school, with a
     * reason. The file must still be the one previewed and the school's
     * records unchanged since; otherwise the import is stopped.
     *
     * @param  array<string, mixed>  $input
     */
    public function approve(User $leader, int $id, array $input): void
    {
        $data = Validator::make($input, self::reasonRules())->validate();
        $outcome = DB::transaction(function () use ($leader, $id, $data): string {
            $import = $this->forLeader($leader, $id);
            if ($import->expires_at->isPast()) {
                $this->finish($import, PlatformSchoolImport::EXPIRED, 'platform_import_expired', null, null);

                return 'expired';
            }
            if (! $this->mayImport($leader, $import->kind)) {
                $this->stop($import, 'approver_left', $leader);

                return 'approver_left';
            }
            $csv = (string) $import->contents;
            if (! hash_equals($import->file_sha256, hash('sha256', $csv))) {
                $this->stop($import, 'file_changed', $leader);

                return 'file_changed';
            }
            $check = $this->imports->check($this->fresh($leader), $import->kind, $csv, $import->mapping, $import->options);
            if (! hash_equals($import->fingerprint, $check['fingerprint'])) {
                $this->stop($import, 'records_changed', $leader);

                return 'records_changed';
            }
            $import->forceFill(['status' => PlatformSchoolImport::APPROVED, 'approved_sha256' => hash('sha256', $csv), 'decision_reason' => $data['reason'], 'decided_at' => now()])->save();
            Audit::record('platform_import_approved', $import, ['status' => PlatformSchoolImport::AWAITING], [
                'import_id' => (int) $import->id, 'status' => PlatformSchoolImport::APPROVED, 'kind' => $import->kind,
                'approved_sha256' => $import->approved_sha256, 'reason' => $data['reason'], 'changed_by' => (int) $leader->id,
            ]);

            return 'approved';
        });
        $this->refuseUnless($outcome, 'approved');
    }

    /** @param  array<string, mixed>  $input */
    public function decline(User $leader, int $id, array $input): void
    {
        $data = Validator::make($input, self::reasonRules())->validate();
        $outcome = DB::transaction(function () use ($leader, $id, $data): string {
            $import = $this->forLeader($leader, $id);
            if ($import->expires_at->isPast()) {
                $this->finish($import, PlatformSchoolImport::EXPIRED, 'platform_import_expired', null, null);

                return 'expired';
            }
            $import->forceFill(['decision_reason' => $data['reason'], 'decided_at' => now()]);
            $this->finish($import, PlatformSchoolImport::DECLINED, 'platform_import_declined', $leader, $data['reason']);

            return 'declined';
        });
        $this->refuseUnless($outcome, 'declined');
    }

    /**
     * Operations apply an approved import: in its own school only, as the
     * leader who approved it, through the school's own import.
     *
     * @return array<string, int> what was written
     */
    public function apply(User $operator, int $id): array
    {
        abort_unless($this->access->can($operator, 'imports.run'), 403);
        $schoolId = $this->schoolOf($id);
        $this->activeSchool($schoolId);
        [$outcome, $counts] = $this->currentSchool->run($schoolId, fn (): array => DB::transaction(function () use ($operator, $id): array {
            // Read again inside the import's own school: no other school's row can be reached.
            $import = PlatformSchoolImport::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($import->isOpen() && $import->expires_at->isPast()) {
                $this->finish($import, PlatformSchoolImport::EXPIRED, 'platform_import_expired', null, null);

                return ['expired', []];
            }
            if ($import->status !== PlatformSchoolImport::APPROVED) {
                throw ValidationException::withMessages(['status' => __('Only an import the school has approved can be applied.')]);
            }
            $csv = (string) $import->contents;
            $hash = hash('sha256', $csv);
            if (! hash_equals((string) $import->approved_sha256, $hash) || ! hash_equals($import->file_sha256, $hash)) {
                $this->stop($import, 'file_changed', $operator);

                return ['file_changed', []];
            }
            $approver = $import->approver_id === null ? null : User::query()->find($import->approver_id);
            if ($approver === null || ! $this->mayImport($approver, $import->kind)) {
                $this->stop($import, 'approver_left', $operator);

                return ['approver_left', []];
            }
            try {
                $counts = $this->imports->commit($this->fresh($approver), $import->kind, $csv, $import->mapping, $import->options, $import->fingerprint);
            } catch (ValidationException $e) {
                if (! array_key_exists('fingerprint', $e->errors())) {
                    throw $e;
                }
                $this->stop($import, 'records_changed', $operator);

                return ['records_changed', []];
            }
            $import->forceFill(['applied_by' => (int) $operator->id, 'applied_at' => now(), 'result' => $counts]);
            $this->finish($import, PlatformSchoolImport::APPLIED, 'platform_import_applied', $operator, null, ['result' => $counts, 'approver_id' => (int) $approver->id]);

            return ['applied', $counts];
        }));
        $this->refuseUnless($outcome, 'applied');

        return $counts;
    }

    /** @param  array<string, mixed>  $input */
    public function cancel(User $operator, int $id, array $input): void
    {
        abort_unless($this->access->can($operator, 'imports.run'), 403);
        $data = Validator::make($input, self::reasonRules())->validate();
        $schoolId = $this->schoolOf($id);
        $this->currentSchool->run($schoolId, fn () => DB::transaction(function () use ($operator, $id, $data): void {
            $import = PlatformSchoolImport::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            if (! $import->isOpen()) {
                throw ValidationException::withMessages(['status' => __('This import is already finished.')]);
            }
            $this->finish($import, PlatformSchoolImport::CANCELLED, 'platform_import_cancelled', $operator, $data['reason']);
        }));
    }

    /** Expires every open import past its date and deletes its file. @return int how many */
    public function expire(): int
    {
        $due = $this->currentSchool->acrossSchools(fn () => PlatformSchoolImport::acrossSchools()->whereIn('status', PlatformSchoolImport::OPEN)
            ->where('expires_at', '<=', now())->orderBy('id')->get(['id', 'school_id']));
        $expired = 0;
        foreach ($due as $row) {
            $expired += (int) $this->currentSchool->run((int) $row->school_id, fn (): bool => DB::transaction(function () use ($row): bool {
                $import = PlatformSchoolImport::query()->whereKey($row->id)->lockForUpdate()->first();
                if ($import === null || ! $import->isOpen() || $import->expires_at->isFuture()) {
                    return false;
                }
                $this->finish($import, PlatformSchoolImport::EXPIRED, 'platform_import_expired', null, null);

                return true;
            }));
        }

        return $expired;
    }

    /**
     * The imports run for one school, newest first (HQ, and the school's own page).
     *
     * @return list<array<string, mixed>>
     */
    public function list(int $schoolId): array
    {
        return $this->currentSchool->run($schoolId, function (): array {
            $imports = PlatformSchoolImport::query()->orderByDesc('id')->limit(100)->get();
            $names = $this->names($imports->pluck('requested_by')->merge($imports->pluck('approver_id')));

            return $imports->map(fn (PlatformSchoolImport $import) => $this->summary($import, $names))->values()->all();
        });
    }

    /** One import with its preview (while open) and every step. @return array<string, mixed> */
    public function show(int $id, ?int $schoolId = null): array
    {
        $schoolId ??= $this->schoolOf($id);

        return $this->currentSchool->run($schoolId, function () use ($id, $schoolId): array {
            $import = PlatformSchoolImport::query()->findOrFail($id);
            $steps = DB::table('audit_log')->where('school_id', $schoolId)
                ->where(fn ($q) => $q->where(fn ($q) => $q->where('subject_type', $import->getMorphClass())->where('subject_id', $import->id))
                    ->orWhere(fn ($q) => $q->where('action', 'platform_import_previewed')->where('created_at', '<=', $import->created_at)
                        ->where('after->file_sha256', $import->file_sha256)->where('after->kind', $import->kind)))
                ->orderBy('created_at')->orderBy('id')->limit(200)->get(['id', 'action', 'actor_id', 'after', 'created_at']);
            $names = $this->names($steps->pluck('actor_id')->merge([$import->requested_by, $import->approver_id, $import->applied_by, $import->closed_by]));

            return $this->summary($import, $names) + [
                'preview' => $import->isOpen() ? ($import->preview ?? []) : [],
                'mapping' => $import->mapping,
                'options' => $import->options,
                'decision_reason' => $import->decision_reason,
                'close_reason' => $import->close_reason,
                'closed_by' => $names[$import->closed_by] ?? null,
                'steps' => $steps->map(function (object $step) use ($names): array {
                    $after = is_string($step->after) ? (array) json_decode($step->after, true) : [];

                    return [
                        'label' => __(self::STEPS[$step->action] ?? $step->action),
                        'at' => $this->local($step->created_at),
                        'actor' => $step->actor_id !== null ? ($names[(int) $step->actor_id] ?? __('Unknown')) : __('Scheduled clean-up'),
                        'reason' => is_string($after['reason'] ?? null) ? $after['reason'] : null,
                        'stopped' => is_string($after['stopped'] ?? null) ? __(self::STOPPED[$after['stopped']] ?? '') : null,
                        'counts' => is_array($after['result'] ?? null) ? $after['result'] : (is_array($after['counts'] ?? null) ? $after['counts'] : null),
                    ];
                })->all(),
            ];
        });
    }

    /** Whether this person is the approving leader of an import that waits for them. */
    public function awaits(User $user, array $import): bool
    {
        return $import['status'] === PlatformSchoolImport::AWAITING && $import['approver_id'] === (int) $user->id && ! $import['expired'];
    }

    /** The school id of an import, for HQ (read across schools), or 404. */
    private function schoolOf(int $id): int
    {
        $schoolId = $this->currentSchool->acrossSchools(fn () => PlatformSchoolImport::acrossSchools()->whereKey($id)->value('school_id'));
        abort_if($schoolId === null, 404);

        return (int) $schoolId;
    }

    /** Imports are run only for a school that is in use. */
    private function activeSchool(int $schoolId): void
    {
        if ($this->school($schoolId)['status'] !== School::ACTIVE) {
            throw ValidationException::withMessages(['school' => __('Imports can only be run for an active school.')]);
        }
    }

    /**
     * The import in the leader's own school (another school's is not found),
     * locked, waiting for this leader's decision.
     */
    private function forLeader(User $leader, int $id): PlatformSchoolImport
    {
        $import = PlatformSchoolImport::query()->whereKey($id)->lockForUpdate()->first();
        abort_if($import === null, 404);
        abort_unless($import->approver_id === (int) $leader->id, 403);
        if ($import->status !== PlatformSchoolImport::AWAITING) {
            throw ValidationException::withMessages(['status' => __('This import is no longer waiting for approval.')]);
        }

        return $import;
    }

    /** A staff member of the current school who may import this kind, or a validation error. */
    private function approver(int $userId, string $kind): User
    {
        $user = User::query()->find($userId);
        if ($user === null || ! $this->mayImport($user, $kind)) {
            throw ValidationException::withMessages(['approver_id' => __('Choose a leader at this school who may import :kind.', ['kind' => mb_strtolower(__(self::KINDS[$kind] ?? $kind))])]);
        }

        return $this->fresh($user);
    }

    /** An active staff member of the current school whose roles there allow importing this kind. */
    private function mayImport(User $user, string $kind): bool
    {
        $member = SchoolMembership::query()->active()->where('school_memberships.school_id', $this->currentSchool->id())
            ->where('school_memberships.user_id', $user->id)->whereNotIn('school_memberships.role', [SchoolRoles::STUDENT, SchoolRoles::PARENT])->exists();
        $fresh = User::query()->whereKey($user->id)->where('status', 1)->first();

        return $member && $fresh !== null && $fresh->can($this->imports->kind($kind)->permission());
    }

    /** The account as stored now, with its roles read in the current school. */
    private function fresh(User $user): User
    {
        return User::query()->findOrFail($user->id);
    }

    /** Stops an open import whose file or school changed, deleting the file. */
    private function stop(PlatformSchoolImport $import, string $why, User $by): void
    {
        $this->finish($import, PlatformSchoolImport::VOID, 'platform_import_stopped', $by, null, ['stopped' => $why]);
    }

    /**
     * Moves an import to a finished state, wipes its file and preview, and
     * records the step.
     *
     * @param  array<string, mixed>  $extra
     */
    private function finish(PlatformSchoolImport $import, string $status, string $action, ?User $by, ?string $reason, array $extra = []): void
    {
        $before = $import->getOriginal('status');
        $fill = ['status' => $status, 'contents' => null, 'preview' => null];
        if ($status !== PlatformSchoolImport::APPLIED && $status !== PlatformSchoolImport::DECLINED) {
            $fill += ['closed_by' => $by?->id, 'close_reason' => $reason];
        }
        $import->forceFill($fill)->save();
        Audit::record($action, $import, ['status' => $before], array_filter([
            'import_id' => (int) $import->id, 'status' => $status, 'kind' => $import->kind, 'file_sha256' => $import->file_sha256,
            'reason' => $reason, 'changed_by' => $by?->id,
        ], fn ($value) => $value !== null) + $extra);
    }

    private function refuseUnless(string $outcome, string $expected): void
    {
        if ($outcome === $expected) {
            return;
        }
        throw ValidationException::withMessages(['status' => match ($outcome) {
            'expired' => __('This import expired and its file was deleted. Navigate Ed can prepare it again.'),
            default => __('This import was stopped and its file deleted: :why Navigate Ed can prepare it again.', ['why' => __(self::STOPPED[$outcome] ?? '')]),
        }]);
    }

    /** @param  array<string, mixed>  $data */
    private function csv(array $data): string
    {
        $csv = SchoolImports::normalise((string) ($data['csv'] ?? ''));
        if ($csv === '') {
            throw ValidationException::withMessages(['csv' => __('Choose a CSV file, or paste its contents.')]);
        }

        return $csv;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>|null null until the columns have been seen
     */
    private function mapping(array $data): ?array
    {
        if (empty($data['mapped'])) {
            return null;
        }

        return array_map(fn ($header) => (string) $header, array_filter((array) ($data['mapping'] ?? []), fn ($header) => $header !== null && $header !== ''));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function options(array $data): array
    {
        return array_filter(Arr::only($data, ['medium_id', 'session_year_id', 'category_id', 'starts_on', 'invite', 'from_sasams']), fn ($value) => $value !== null && $value !== false && $value !== '');
    }

    /**
     * @param  array<int, string>  $names
     * @return array<string, mixed>
     */
    private function summary(PlatformSchoolImport $import, array $names): array
    {
        $expired = $import->isOpen() && $import->expires_at->isPast();

        return [
            'id' => (int) $import->id,
            'school_id' => (int) $import->school_id,
            'kind' => $import->kind,
            'kind_label' => __(self::KINDS[$import->kind] ?? $import->kind),
            'status' => $import->status,
            'status_label' => $expired ? __('Expired') : __(self::STATUSES[$import->status] ?? $import->status),
            'expired' => $expired,
            'rows' => $import->rows,
            'counts' => $import->counts,
            'result' => $import->result,
            'file_sha256' => $import->file_sha256,
            'requested_by' => $names[$import->requested_by] ?? null,
            'approver_id' => $import->approver_id,
            'approver' => $names[$import->approver_id] ?? null,
            'sent_at' => $this->local($import->created_at),
            'expires_at' => $this->local($import->expires_at),
            'decided_at' => $this->local($import->decided_at),
            'applied_at' => $this->local($import->applied_at),
        ];
    }

    /**
     * People named on imports: platform staff and school leaders, read
     * across schools (an operator belongs to none).
     *
     * @param  iterable<int|null>  $ids
     * @return array<int, string>
     */
    private function names(iterable $ids): array
    {
        $ids = collect($ids)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        return $ids->isEmpty() ? [] : User::acrossSchools()->withTrashed()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name)])->all();
    }

    private function local(mixed $at): ?string
    {
        if ($at === null) {
            return null;
        }
        $instant = $at instanceof DateTimeInterface ? CarbonImmutable::instance($at) : CarbonImmutable::parse((string) $at, config('app.timezone'));

        return $instant->setTimezone(self::TIMEZONE)->format('Y-m-d H:i');
    }
}
