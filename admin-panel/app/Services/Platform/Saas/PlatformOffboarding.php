<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Jobs\Platform\PrepareOffboardingExport;
use App\Models\Platform\School;
use App\Models\Platform\SchoolContract;
use App\Models\Platform\SchoolOffboarding;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Offboarding\OffboardingDeletionPlan;
use App\Services\Platform\Offboarding\OffboardingExport;
use App\Services\Platform\Offboarding\SchoolExit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * School offboarding from HQ (Action Plan SA.11). Navigate Ed is the
 * operator and the school the responsible party (POPIA sections 20 and 21),
 * so a leaving school first gets all of its own data back, then is archived,
 * and its data is deleted when the contract says:
 *
 * 1. Operations (or an Owner) starts the offboarding with a reason, the
 *    contract's retention terms (from school_contracts when recorded,
 *    otherwise entered) and a receipt deadline;
 * 2. the export is built (OffboardingExport) and offered to the school's Super
 *    Admins only, never to HQ staff;
 * 3. a Super Admin confirms receipt (SchoolExit), or the deadline passes;
 * 4. the school is archived (PlatformSchools::changeStatus) and deletion is
 *    scheduled for the contract's date;
 * 5. deletion needs a request and a second approval by a different person, an
 *    Owner, each re-checking that the export was delivered and no legal hold
 *    is on. This step runs the deletion dry run only: nothing is deleted.
 *
 * Every step is audited with ids, states, dates and counts (never free
 * text), and HQ sees counts and states only, never a row of the school's data.
 * engineering/specs/platform/offboarding.md.
 */
class PlatformOffboarding
{
    /** HQ works in South African time. */
    public const TIMEZONE = 'Africa/Johannesburg';

    public const STATES = [
        SchoolOffboarding::PREPARING => 'Preparing the export',
        SchoolOffboarding::FAILED => 'Export failed',
        SchoolOffboarding::READY => 'Waiting for the school to confirm receipt',
        SchoolOffboarding::RECEIVED => 'Export received; ready to archive',
        SchoolOffboarding::ARCHIVED => 'Archived; deletion scheduled',
        SchoolOffboarding::CANCELLED => 'Cancelled',
    ];

    public function __construct(
        private readonly CurrentSchool $currentSchool,
        private readonly OffboardingExport $export,
        private readonly OffboardingDeletionPlan $plan,
    ) {}

    public static function startRules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'receipt_deadline' => ['required', 'date_format:Y-m-d'],
            'delete_on' => ['nullable', 'date_format:Y-m-d'],
            'retention_terms' => ['nullable', 'string', 'min:10', 'max:500'],
        ];
    }

    public static function reasonRules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:500']];
    }

    public static function reference(int $id): string
    {
        return 'OFF-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * The school's offboarding page: its current (or last) offboarding, the
     * contract's retention terms, and how many Super Admins could receive the
     * export. Counts and states only.
     *
     * @return array<string, mixed>
     */
    public function show(int $schoolId): array
    {
        $school = app(PlatformSchools::class)->find($schoolId);
        abort_if($school === null, 404);

        return $this->currentSchool->run($schoolId, function () use ($school): array {
            $row = SchoolOffboarding::query()->where('state', '!=', SchoolOffboarding::CANCELLED)->latest('id')->first();
            $past = SchoolOffboarding::query()->where('state', SchoolOffboarding::CANCELLED)->latest('id')->limit(20)->get();

            return [
                'school' => $school,
                'offboarding' => $row === null ? null : $this->present($row),
                'cancelled' => $past->map(fn (SchoolOffboarding $old): array => ['reference' => self::reference((int) $old->id), 'cancelled_at' => $this->local($old->cancelled_at)])->all(),
                'contract' => $this->contractTerms(),
                'administrators' => count(SchoolExit::administratorIds()),
                'minimumDeadline' => $this->today()->addDays((int) config('offboarding.minimum_receipt_days'))->toDateString(),
            ];
        });
    }

    /** Step 1: start, and queue the export. */
    public function start(int $schoolId, array $input, User $actor): int
    {
        $this->authorize($actor, 'offboarding.manage');
        $data = Validator::make($input, self::startRules())->validate();
        $school = $this->school($schoolId);
        if ((int) $school->id === CurrentSchool::defaultId()) {
            throw ValidationException::withMessages(['reason' => __('The install\'s default school cannot be offboarded: public pages and sign-in run in it.')]);
        }
        if ($school->status !== School::ACTIVE) {
            throw ValidationException::withMessages(['reason' => __('Restore the school first: its Super Admin must be able to sign in to collect the export.')]);
        }

        return $this->currentSchool->run($schoolId, fn (): int => DB::transaction(function () use ($data, $actor, $schoolId): int {
            School::query()->whereKey($schoolId)->lockForUpdate()->firstOrFail();
            abort_if(SchoolOffboarding::query()->where('state', '!=', SchoolOffboarding::CANCELLED)->exists(), 409, __('This school already has an offboarding in progress.'));
            if (SchoolExit::administratorIds() === []) {
                throw ValidationException::withMessages(['reason' => __('The school has no active Super Admin to receive its export. Set one up first.')]);
            }
            $deadline = CarbonImmutable::createFromFormat('Y-m-d', $data['receipt_deadline'], self::TIMEZONE)->startOfDay();
            $earliest = $this->today()->addDays((int) config('offboarding.minimum_receipt_days'));
            if ($deadline->lt($earliest)) {
                throw ValidationException::withMessages(['receipt_deadline' => __('Give the school until at least :date to collect its export.', ['date' => $earliest->toDateString()])]);
            }
            [$source, $deleteOn, $terms, $contractId] = $this->retention($data, $deadline);
            $row = SchoolOffboarding::query()->create([
                'state' => SchoolOffboarding::PREPARING, 'reason' => $data['reason'], 'contract_id' => $contractId, 'retention_source' => $source,
                'retention_terms' => $terms, 'delete_on' => $deleteOn->toDateString(), 'receipt_deadline' => $deadline->toDateString(),
                'started_by' => $actor->id, 'generation_id' => (string) Str::uuid(),
            ]);
            Audit::record('platform_school_offboarding_started', $row, [], ['offboarding_id' => (int) $row->id, 'state' => $row->state, 'retention_source' => $source,
                'contract_id' => $contractId, 'delete_on' => $deleteOn->toDateString(), 'receipt_deadline' => $deadline->toDateString(), 'started_by' => (int) $actor->id]);
            PrepareOffboardingExport::dispatch($schoolId, (int) $row->id, (string) $row->generation_id)->afterCommit();

            return (int) $row->id;
        }, 3));
    }

    /** Build the export again after a failure. */
    public function retry(int $schoolId, User $actor): void
    {
        $this->authorize($actor, 'offboarding.manage');
        $this->write($schoolId, function (SchoolOffboarding $row) use ($schoolId): void {
            abort_unless($row->state === SchoolOffboarding::FAILED, 409, __('Only a failed export can be prepared again.'));
            $row->forceFill(['state' => SchoolOffboarding::PREPARING, 'failure_code' => null, 'generation_id' => (string) Str::uuid()])->save();
            Audit::record('platform_school_offboarding_export_retried', $row, ['state' => SchoolOffboarding::FAILED], ['offboarding_id' => (int) $row->id, 'state' => $row->state]);
            PrepareOffboardingExport::dispatch($schoolId, (int) $row->id, (string) $row->generation_id)->afterCommit();
        });
    }

    /** Step 4: archive the school once the export was received; deletion is scheduled for the contract's date. */
    public function archive(int $schoolId, User $actor): void
    {
        $this->authorize($actor, 'offboarding.manage');
        $this->write($schoolId, function (SchoolOffboarding $row) use ($schoolId, $actor): void {
            abort_unless($row->state === SchoolOffboarding::RECEIVED, 409, __('Archive the school only after it has received its export.'));
            app(PlatformSchools::class)->changeStatus($schoolId, School::ARCHIVED,
                'School offboarding '.self::reference((int) $row->id).': export received; data deletion scheduled for '.$row->delete_on->toDateString().'.', $actor);
            $row->forceFill(['state' => SchoolOffboarding::ARCHIVED, 'archived_at' => now(), 'archived_by' => $actor->id])->save();
            Audit::record('platform_school_offboarding_archived', $row, ['state' => SchoolOffboarding::RECEIVED], ['offboarding_id' => (int) $row->id, 'state' => $row->state,
                'delete_on' => $row->delete_on->toDateString(), 'archived_by' => (int) $actor->id]);
        });
    }

    /** Stop an offboarding: the export copy is discarded. An archived school stays archived until restored. */
    public function cancel(int $schoolId, array $input, User $actor): void
    {
        $this->authorize($actor, 'offboarding.manage');
        Validator::make($input, self::reasonRules())->validate();
        $this->write($schoolId, function (SchoolOffboarding $row) use ($actor): void {
            $before = $row->state;
            $this->export->discard($row);
            $row->forceFill(['state' => SchoolOffboarding::CANCELLED, 'cancelled_at' => now(), 'cancelled_by' => $actor->id, 'artifact_path' => null,
                'deletion_requested_at' => null, 'deletion_requested_by' => null, 'deletion_approved_at' => null, 'deletion_approved_by' => null])->save();
            Audit::record('platform_school_offboarding_cancelled', $row, ['state' => $before], ['offboarding_id' => (int) $row->id, 'state' => $row->state, 'cancelled_by' => (int) $actor->id]);
        });
    }

    /** The deletion dry run: counts per table, stored on the offboarding. Deletes nothing. */
    public function dryRun(int $schoolId, User $actor): void
    {
        $this->authorize($actor, 'offboarding.manage');
        $this->write($schoolId, function (SchoolOffboarding $row) use ($schoolId, $actor): void {
            abort_unless($row->state === SchoolOffboarding::ARCHIVED, 409, __('Run the deletion dry run once the school is archived.'));
            $this->storeDryRun($row, $schoolId, $actor);
        });
    }

    /** Step 5, first approval: ask for the school's deletion. */
    public function requestDeletion(int $schoolId, User $actor): void
    {
        $this->authorize($actor, 'offboarding.manage');
        $this->write($schoolId, function (SchoolOffboarding $row) use ($schoolId, $actor): void {
            $this->deletable($row, $schoolId);
            abort_if($row->deletion_requested_at !== null, 409, __('Deletion has already been asked for.'));
            $row->forceFill(['deletion_requested_at' => now(), 'deletion_requested_by' => $actor->id])->save();
            Audit::record('platform_school_offboarding_deletion_requested', $row, [], ['offboarding_id' => (int) $row->id, 'requested_by' => (int) $actor->id, 'export_delivered' => true]);
        });
    }

    /**
     * Step 5, second approval: an Owner who is not the person who asked. The
     * export and legal hold are checked again, and the dry run is run and
     * stored. Deleting the rows is not built in this step: nothing is deleted.
     */
    public function approveDeletion(int $schoolId, User $actor): void
    {
        $this->authorize($actor, 'offboarding.approve');
        $this->write($schoolId, function (SchoolOffboarding $row) use ($schoolId, $actor): void {
            $this->deletable($row, $schoolId);
            abort_if($row->deletion_requested_at === null, 409, __('Deletion has to be asked for before it can be approved.'));
            abort_if($row->deletion_approved_at !== null, 409, __('Deletion has already been approved.'));
            if ((int) $row->deletion_requested_by === (int) $actor->id) {
                throw ValidationException::withMessages(['approval' => __('The second approval must come from someone other than the person who asked for deletion.')]);
            }
            $row->forceFill(['deletion_approved_at' => now(), 'deletion_approved_by' => $actor->id])->save();
            Audit::record('platform_school_offboarding_deletion_approved', $row, [], ['offboarding_id' => (int) $row->id, 'requested_by' => (int) $row->deletion_requested_by,
                'approved_by' => (int) $actor->id, 'export_delivered' => true, 'delete_on' => $row->delete_on->toDateString()]);
            $this->storeDryRun($row, $schoolId, $actor);
        });
    }

    /** A legal hold stops deletion and voids any approvals given so far. */
    public function placeHold(int $schoolId, array $input, User $actor): void
    {
        $this->authorize($actor, 'offboarding.manage');
        $data = Validator::make($input, self::reasonRules())->validate();
        $this->write($schoolId, function (SchoolOffboarding $row) use ($data, $actor): void {
            abort_if($row->legal_hold, 409, __('A legal hold is already on.'));
            $voided = $row->deletion_requested_at !== null;
            $row->forceFill(['legal_hold' => true, 'legal_hold_reason' => $data['reason'], 'legal_hold_at' => now(), 'legal_hold_by' => $actor->id,
                'deletion_requested_at' => null, 'deletion_requested_by' => null, 'deletion_approved_at' => null, 'deletion_approved_by' => null])->save();
            Audit::record('platform_school_offboarding_legal_hold_placed', $row, ['legal_hold' => false], ['offboarding_id' => (int) $row->id, 'legal_hold' => true,
                'approvals_voided' => $voided, 'placed_by' => (int) $actor->id]);
        });
    }

    public function liftHold(int $schoolId, User $actor): void
    {
        $this->authorize($actor, 'offboarding.approve');
        $this->write($schoolId, function (SchoolOffboarding $row) use ($actor): void {
            abort_unless($row->legal_hold, 409, __('No legal hold is on.'));
            $row->forceFill(['legal_hold' => false, 'legal_hold_at' => now(), 'legal_hold_by' => $actor->id])->save();
            Audit::record('platform_school_offboarding_legal_hold_lifted', $row, ['legal_hold' => true], ['offboarding_id' => (int) $row->id, 'legal_hold' => false, 'lifted_by' => (int) $actor->id]);
        });
    }

    /**
     * Step 3's alternative: a school that did not confirm by its deadline is
     * taken to have received its export. Run daily by the scheduler.
     */
    public function lapseDeadlines(): int
    {
        $due = $this->currentSchool->acrossSchools(fn () => SchoolOffboarding::acrossSchools()->where('state', SchoolOffboarding::READY)
            ->join('schools', 'schools.id', '=', 'school_offboardings.school_id')
            ->get(['school_offboardings.id', 'school_offboardings.school_id', 'school_offboardings.receipt_deadline', 'schools.timezone']));
        $lapsed = 0;
        foreach ($due as $item) {
            if (CarbonImmutable::now((string) ($item->timezone ?: self::TIMEZONE))->toDateString() <= $item->receipt_deadline->toDateString()) {
                continue;
            }
            $lapsed += (int) $this->currentSchool->run((int) $item->school_id, fn (): bool => DB::transaction(function () use ($item): bool {
                School::query()->whereKey($item->school_id)->lockForUpdate()->firstOrFail();
                $row = SchoolOffboarding::query()->where('state', SchoolOffboarding::READY)->lockForUpdate()->find($item->id);
                if ($row === null) {
                    return false;
                }
                $row->forceFill(['state' => SchoolOffboarding::RECEIVED, 'received_at' => now(), 'receipt_basis' => 'deadline'])->save();
                Audit::record('platform_school_offboarding_receipt_deadline_passed', $row, ['state' => SchoolOffboarding::READY], ['offboarding_id' => (int) $row->id,
                    'state' => $row->state, 'receipt_basis' => 'deadline', 'receipt_deadline' => $row->receipt_deadline->toDateString(), 'downloads' => (int) $row->download_count]);

                return true;
            }));
        }

        return $lapsed;
    }

    /** The checks both deletion approvals repeat. */
    private function deletable(SchoolOffboarding $row, int $schoolId): void
    {
        abort_unless($row->state === SchoolOffboarding::ARCHIVED, 409, __('Deletion can be approved only for an archived school.'));
        abort_if($row->legal_hold, 409, __('A legal hold is on: the school\'s data cannot be deleted.'));
        abort_unless(DB::table('schools')->where('id', $schoolId)->value('status') === School::ARCHIVED, 409, __('The school is no longer archived.'));
        abort_unless($row->received_at !== null && $row->receipt_basis !== null && $this->export->intact($row), 409,
            __('The school\'s export could not be confirmed as delivered and intact. Nothing can be deleted.'));
    }

    private function storeDryRun(SchoolOffboarding $row, int $schoolId, User $actor): void
    {
        $report = $this->plan->report($schoolId);
        $row->forceFill(['dry_run_report' => $report, 'dry_run_at' => now(), 'dry_run_by' => $actor->id])->save();
        Audit::record('platform_school_offboarding_dry_run', $row, [], ['offboarding_id' => (int) $row->id, 'tables_with_rows' => count($report['delete']),
            'rows_to_delete' => $report['delete_total'], 'deleted' => 0]);
    }

    /**
     * From the latest contract when it records an end and a deletion term,
     * otherwise as entered. The data is never deleted before the school's
     * deadline to collect it: a contract date that falls on or before the
     * deadline moves to the day after it, and the terms say so.
     *
     * @return array{0: string, 1: CarbonImmutable, 2: string, 3: ?int}
     */
    private function retention(array $data, CarbonImmutable $deadline): array
    {
        $terms = $this->contractTerms();
        if ($terms !== null && $terms['delete_on'] !== null) {
            $deleteOn = CarbonImmutable::createFromFormat('Y-m-d', $terms['delete_on'], self::TIMEZONE)->startOfDay();
            $text = __('Contract from :starts: the school\'s data is deleted :days days after it ends on :ends.', ['starts' => $terms['starts_on'], 'days' => $terms['deletion_days_after_end'], 'ends' => $terms['ends_on']]);
            if ($deleteOn->lte($deadline)) {
                $deleteOn = $deadline->addDay();
                $text .= ' '.__('That date is not after the school\'s deadline to collect its export, so deletion is scheduled for the day after the deadline.');
            }

            return ['contract', $deleteOn, Str::limit($text, 500, ''), $terms['id']];
        }
        $errors = [];
        if (($data['delete_on'] ?? null) === null) {
            $errors['delete_on'] = __('The contract records no deletion term: enter the date the school\'s data is to be deleted.');
        }
        if (($data['retention_terms'] ?? null) === null) {
            $errors['retention_terms'] = __('The contract records no deletion term: enter the terms you are following.');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
        $deleteOn = CarbonImmutable::createFromFormat('Y-m-d', $data['delete_on'], self::TIMEZONE)->startOfDay();
        if ($deleteOn->lte($deadline)) {
            throw ValidationException::withMessages(['delete_on' => __('The deletion date must be after the school\'s deadline to collect its export.')]);
        }

        return ['entered', $deleteOn, $data['retention_terms'], $terms['id'] ?? null];
    }

    /**
     * The latest contract's retention terms, inside the school.
     *
     * @return array{id: int, starts_on: string, ends_on: ?string, deletion_days_after_end: ?int, delete_on: ?string}|null
     */
    private function contractTerms(): ?array
    {
        $contract = SchoolContract::query()->orderByDesc('starts_on')->orderByDesc('id')->first();
        if ($contract === null) {
            return null;
        }
        $deleteOn = $contract->ends_on !== null && $contract->deletion_days_after_end !== null
            ? CarbonImmutable::parse($contract->ends_on->toDateString())->addDays($contract->deletion_days_after_end)->toDateString() : null;

        return ['id' => (int) $contract->id, 'starts_on' => $contract->starts_on->toDateString(), 'ends_on' => $contract->ends_on?->toDateString(),
            'deletion_days_after_end' => $contract->deletion_days_after_end, 'delete_on' => $deleteOn];
    }

    /** @return array<string, mixed> */
    private function present(SchoolOffboarding $row): array
    {
        $names = $this->actorNames([$row->started_by, $row->archived_by, $row->legal_hold_by, $row->deletion_requested_by, $row->deletion_approved_by, $row->dry_run_by]);

        return [
            'id' => (int) $row->id, 'reference' => self::reference((int) $row->id), 'state' => $row->state, 'state_label' => self::STATES[$row->state] ?? $row->state,
            'reason' => $row->reason, 'retention_source' => $row->retention_source, 'retention_terms' => $row->retention_terms,
            'delete_on' => $row->delete_on->toDateString(), 'receipt_deadline' => $row->receipt_deadline->toDateString(),
            'started_by' => $names[$row->started_by] ?? null, 'started_at' => $this->local($row->created_at),
            'failure_code' => $row->failure_code, 'export_ready_at' => $this->local($row->export_ready_at),
            'tables' => $row->table_count, 'rows' => $row->row_count, 'bytes' => $row->artifact_bytes, 'sha256' => $row->artifact_sha256,
            'downloads' => (int) $row->download_count, 'last_downloaded_at' => $this->local($row->last_downloaded_at),
            'received_at' => $this->local($row->received_at), 'receipt_basis' => $row->receipt_basis,
            'archived_at' => $this->local($row->archived_at), 'archived_by' => $names[$row->archived_by] ?? null,
            'legal_hold' => $row->legal_hold, 'legal_hold_reason' => $row->legal_hold_reason, 'legal_hold_at' => $this->local($row->legal_hold_at), 'legal_hold_by' => $names[$row->legal_hold_by] ?? null,
            'deletion_requested_at' => $this->local($row->deletion_requested_at), 'deletion_requested_by' => $names[$row->deletion_requested_by] ?? null,
            'deletion_requested_by_id' => $row->deletion_requested_by === null ? null : (int) $row->deletion_requested_by,
            'deletion_approved_at' => $this->local($row->deletion_approved_at), 'deletion_approved_by' => $names[$row->deletion_approved_by] ?? null,
            'dry_run' => $row->dry_run_report, 'dry_run_at' => $this->local($row->dry_run_at), 'dry_run_by' => $names[$row->dry_run_by] ?? null,
        ];
    }

    /**
     * Platform staff names (they are not members of the school, so looked up across schools).
     *
     * @param  list<mixed>  $ids
     * @return array<int, string>
     */
    private function actorNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        return $this->currentSchool->acrossSchools(fn (): array => User::withTrashed()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $user): array => [(int) $user->id => trim($user->first_name.' '.$user->last_name)])->all());
    }

    /** Lock the school and its open offboarding, then change it. */
    private function write(int $schoolId, callable $change): void
    {
        $this->school($schoolId);
        $this->currentSchool->run($schoolId, fn () => DB::transaction(function () use ($schoolId, $change): void {
            School::query()->whereKey($schoolId)->lockForUpdate()->firstOrFail();
            $row = SchoolOffboarding::query()->where('state', '!=', SchoolOffboarding::CANCELLED)->latest('id')->lockForUpdate()->first();
            abort_if($row === null, 404);
            $change($row);
        }, 3));
    }

    private function school(int $schoolId): School
    {
        return $this->currentSchool->acrossSchools(fn (): School => School::query()->findOrFail($schoolId));
    }

    private function authorize(User $actor, string $ability): void
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, $ability), 403);
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)->startOfDay();
    }

    private function local(mixed $at): ?string
    {
        return $at === null ? null : CarbonImmutable::parse($at)->timezone(self::TIMEZONE)->format('Y-m-d H:i');
    }
}
