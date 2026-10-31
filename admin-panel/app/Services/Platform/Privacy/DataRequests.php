<?php

declare(strict_types=1);

namespace App\Services\Platform\Privacy;

use App\Models\Platform\DataRequest;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * POPIA data subject requests that reach Navigate Ed (Action Plan SA.11).
 * Navigate Ed is the schools' operator (POPIA section 21); each school is the
 * responsible party that decides and answers. HQ logs a request with its
 * deadline and passes it to the school; the school's Super Admin records the
 * answer it gave the requester; HQ closes it, or records that it was withdrawn.
 *
 * The requester's details and every free-text note are encrypted at rest and
 * never written to the audit log, which records ids, kinds and states only.
 * engineering/specs/platform/data-requests.md.
 */
class DataRequests
{
    public const TIMEZONE = 'Africa/Johannesburg';

    public const ABILITY = 'privacy.manage';

    /** PAIA section 25 gives 30 days for an access request; HQ uses it for every kind unless it sets an earlier or agreed later date. */
    public const DEFAULT_DAYS = 30;

    public const MAX_DAYS = 90;

    public const KINDS = [
        'access' => 'See the personal information held (section 23)',
        'correction' => 'Correct personal information (section 24)',
        'deletion' => 'Delete personal information (section 24)',
        'objection' => 'Object to processing (section 11(3))',
    ];

    public const CHANNELS = ['email' => 'Email', 'phone' => 'Phone call', 'letter' => 'Letter', 'form' => 'POPIA form', 'in_person' => 'In person'];

    public const REQUESTER_ROLES = ['parent' => 'Parent or guardian', 'learner' => 'Learner', 'former_learner' => 'Former learner', 'staff' => 'Staff member', 'other' => 'Someone else'];

    public const STATUSES = ['received' => 'Received', 'with_school' => 'With the school', 'answered' => 'Answered by the school', 'closed' => 'Closed', 'withdrawn' => 'Withdrawn'];

    public const OUTCOMES = ['fulfilled' => 'Done as asked', 'partly' => 'Partly done', 'refused' => 'Refused'];

    public const OPEN = ['received', 'with_school', 'answered'];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    public static function logRules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'kind' => ['required', Rule::in(array_keys(self::KINDS))],
            'channel' => ['required', Rule::in(array_keys(self::CHANNELS))],
            'requester_role' => ['required', Rule::in(array_keys(self::REQUESTER_ROLES))],
            'requester_name' => ['required', 'string', 'min:2', 'max:191'],
            'requester_contact' => ['required', 'string', 'min:3', 'max:191'],
            'details' => ['required', 'string', 'min:10', 'max:2000'],
            'received_on' => ['required', 'date_format:Y-m-d'],
            'due_on' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public static function noteRules(): array
    {
        return ['note' => ['required', 'string', 'min:5', 'max:500']];
    }

    public static function answerRules(): array
    {
        return ['outcome' => ['required', Rule::in(array_keys(self::OUTCOMES))], 'note' => ['required', 'string', 'min:10', 'max:2000']];
    }

    public static function reference(int $id): string
    {
        return 'DR-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    /** Whether this person is a school's Super Admin in the current school: by default its Information Officer. */
    public static function isSchoolOfficer(?User $user): bool
    {
        return $user !== null && $user->hasRole('Super Admin');
    }

    // HQ ---------------------------------------------------------------

    /** @return array{open: list<array<string, mixed>>, closed: list<array<string, mixed>>} */
    public function list(User $actor): array
    {
        $this->authoriseHq($actor);

        return $this->currentSchool->acrossSchools(function (): array {
            $names = DB::table('schools')->pluck('name', 'id');
            $open = DataRequest::acrossSchools()->whereIn('status', self::OPEN)->orderBy('due_on')->orderBy('id')->limit(500)->get();
            $closed = DataRequest::acrossSchools()->whereIn('status', ['closed', 'withdrawn'])->orderByDesc('closed_at')->orderByDesc('id')->limit(50)->get();
            $row = fn (DataRequest $r): array => $this->summary($r) + ['school' => (string) ($names[$r->school_id] ?? '')];

            return ['open' => $open->map($row)->values()->all(), 'closed' => $closed->map($row)->values()->all()];
        });
    }

    /** One request with its requester, the school's answer and the school's privacy contacts. */
    public function show(User $actor, int $id): array
    {
        $this->authoriseHq($actor);

        return $this->currentSchool->acrossSchools(function () use ($id): array {
            $row = DataRequest::acrossSchools()->findOrFail($id);
            // The school's Information Officer and principal from HQ's own customer record (SA.4): business contacts only.
            $contacts = DB::table('school_contacts')->where('school_id', $row->school_id)->whereIn('role', ['information_officer', 'principal'])
                ->orderByRaw("role = 'information_officer' desc")->orderBy('name')->get(['role', 'name', 'email', 'phone'])->map(fn (object $c) => (array) $c)->all();

            return ['request' => $this->detail($row) + ['school' => (string) DB::table('schools')->where('id', $row->school_id)->value('name')], 'contacts' => $contacts];
        });
    }

    /** @return array<int, string> school id => name */
    public function schoolChoices(User $actor): array
    {
        $this->authoriseHq($actor);

        return $this->currentSchool->acrossSchools(fn (): array => DB::table('schools')->orderBy('name')->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(int) $id => (string) $name])->all());
    }

    public function log(User $actor, array $input): int
    {
        $this->authoriseHq($actor);
        $data = Validator::make($input, self::logRules())->validate();
        $today = CarbonImmutable::now(self::TIMEZONE)->startOfDay();
        $received = CarbonImmutable::createFromFormat('!Y-m-d', $data['received_on'], self::TIMEZONE);
        if ($received->greaterThan($today)) {
            throw ValidationException::withMessages(['received_on' => __('A request cannot be received in the future.')]);
        }
        if ($received->lessThan($today->subYear())) {
            throw ValidationException::withMessages(['received_on' => __('Log requests received in the last year only.')]);
        }
        $due = isset($data['due_on']) ? CarbonImmutable::createFromFormat('!Y-m-d', $data['due_on'], self::TIMEZONE) : $received->addDays(self::DEFAULT_DAYS);
        if ($due->lessThan($received) || $due->greaterThan($received->addDays(self::MAX_DAYS))) {
            throw ValidationException::withMessages(['due_on' => __('The answer is due between the day it was received and :days days later.', ['days' => self::MAX_DAYS])]);
        }

        return $this->currentSchool->acrossSchools(fn (): int => DB::transaction(function () use ($data, $received, $due, $actor): int {
            $row = new DataRequest;
            $row->forceFill([
                'school_id' => (int) $data['school_id'], 'kind' => $data['kind'], 'status' => 'received', 'channel' => $data['channel'],
                'requester_role' => $data['requester_role'], 'requester_name' => $data['requester_name'], 'requester_contact' => $data['requester_contact'],
                'details' => $data['details'], 'received_on' => $received->toDateString(), 'due_on' => $due->toDateString(), 'logged_by' => $actor->id,
            ])->save();
            Audit::record('platform_data_request_logged', null, [], ['data_request_id' => $row->id, 'kind' => $row->kind, 'due_on' => $due->toDateString(), 'changed_by' => $actor->id], (int) $row->school_id);

            return (int) $row->id;
        }));
    }

    /** Passes a received request to the school and tells its Super Admins, naming no one. */
    public function pass(User $actor, int $id): void
    {
        $this->authoriseHq($actor);
        $this->currentSchool->acrossSchools(fn () => DB::transaction(function () use ($actor, $id): void {
            $row = DataRequest::acrossSchools()->lockForUpdate()->findOrFail($id);
            abort_unless($row->status === 'received', 409, __('This request has already been passed to the school.'));
            $officers = $this->officers((int) $row->school_id);
            if ($officers === []) {
                throw ValidationException::withMessages(['request' => __('This school has no active Super Admin to receive the request. Contact the school first.')]);
            }
            $row->forceFill(['status' => 'with_school', 'passed_at' => now(), 'passed_by' => $actor->id])->save();
            $this->currentSchool->run((int) $row->school_id, function () use ($row, $officers): void {
                $notifier = app(Notifier::class);
                $notice = $notifier->makeNotice('school.data_request', __('A data request needs your answer'),
                    __('Navigate Ed has passed a POPIA request to the school. Please answer it by :date.', ['date' => $row->due_on->toDateString()]), '/data-requests');
                $notifier->assign($notice, $officers);
            });
            Audit::record('platform_data_request_passed', null, ['status' => 'received'], ['data_request_id' => $row->id, 'status' => 'with_school', 'recipients' => count($officers), 'changed_by' => $actor->id], (int) $row->school_id);
        }));
    }

    /** Closes an answered request once Navigate Ed has done what it needs to. */
    public function close(User $actor, int $id, array $input): void
    {
        $this->finish($actor, $id, $input, ['answered'], 'closed', 'platform_data_request_closed');
    }

    /** Records that the requester withdrew a request the school has not answered yet. */
    public function withdraw(User $actor, int $id, array $input): void
    {
        $this->finish($actor, $id, $input, ['received', 'with_school'], 'withdrawn', 'platform_data_request_withdrawn');
    }

    // School -----------------------------------------------------------

    /** The current school's requests that Navigate Ed has passed to it: open ones first, by deadline. */
    public function forSchool(User $actor): array
    {
        $this->authoriseSchool($actor);
        $rows = DataRequest::query()->whereIn('status', ['with_school', 'answered', 'closed'])->orderByRaw("status = 'with_school' desc")->orderBy('due_on')->orderByDesc('id')->limit(200)->get();

        return $rows->map(fn (DataRequest $r) => $this->detail($r))->values()->all();
    }

    public function answer(User $actor, int $id, array $input): void
    {
        $this->authoriseSchool($actor);
        $data = Validator::make($input, self::answerRules())->validate();
        DB::transaction(function () use ($actor, $id, $data): void {
            $row = DataRequest::query()->whereIn('status', ['with_school', 'answered', 'closed'])->lockForUpdate()->findOrFail($id);
            abort_unless($row->status === 'with_school', 409, __('This request has already been answered.'));
            $row->forceFill(['status' => 'answered', 'outcome' => $data['outcome'], 'outcome_note' => $data['note'], 'answered_at' => now(), 'answered_by' => $actor->id])->save();
            Audit::record('platform_data_request_answered', null, ['status' => 'with_school'], ['data_request_id' => $row->id, 'status' => 'answered', 'outcome' => $data['outcome'], 'changed_by' => $actor->id], (int) $row->school_id);
        });
    }

    // Shared -----------------------------------------------------------

    private function finish(User $actor, int $id, array $input, array $from, string $to, string $action): void
    {
        $this->authoriseHq($actor);
        $data = Validator::make($input, self::noteRules())->validate();
        $this->currentSchool->acrossSchools(fn () => DB::transaction(function () use ($actor, $id, $data, $from, $to, $action): void {
            $row = DataRequest::acrossSchools()->lockForUpdate()->findOrFail($id);
            abort_unless(in_array($row->status, $from, true), 409, __('This request cannot be :action now.', ['action' => $to === 'closed' ? __('closed') : __('withdrawn')]));
            $before = $row->status;
            $row->forceFill(['status' => $to, 'closing_note' => $data['note'], 'closed_at' => now(), 'closed_by' => $actor->id])->save();
            Audit::record($action, null, ['status' => $before], ['data_request_id' => $row->id, 'status' => $to, 'changed_by' => $actor->id], (int) $row->school_id);
        }));
    }

    private function authoriseHq(User $actor): void
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, self::ABILITY), 403);
    }

    private function authoriseSchool(User $actor): void
    {
        abort_unless(self::isSchoolOfficer($actor), 403);
    }

    /** @return list<int> the school's active Super Admins */
    private function officers(int $schoolId): array
    {
        return $this->currentSchool->run($schoolId, fn (): array => SchoolMembership::active()->where('school_id', $schoolId)->where('role', 'Super Admin')
            ->distinct()->pluck('user_id')->map(fn ($id) => (int) $id)->values()->all());
    }

    /** The list fields: no requester details. */
    private function summary(DataRequest $row): array
    {
        $today = CarbonImmutable::now(self::TIMEZONE)->startOfDay();
        $due = CarbonImmutable::parse($row->due_on->toDateString(), self::TIMEZONE);
        $open = in_array($row->status, ['received', 'with_school'], true);

        return [
            'id' => (int) $row->id, 'reference' => self::reference((int) $row->id), 'kind' => $row->kind, 'status' => $row->status,
            'received_on' => $row->received_on->toDateString(), 'due_on' => $row->due_on->toDateString(),
            'days_left' => $open ? (int) $today->diffInDays($due, false) : null, 'overdue' => $open && $due->lessThan($today),
            'outcome' => $row->outcome,
        ];
    }

    /** Everything about one request, for HQ and for the school it belongs to. */
    private function detail(DataRequest $row): array
    {
        $names = $this->actorNames([$row->logged_by, $row->passed_by, $row->answered_by, $row->closed_by]);
        $local = fn ($at) => $at === null ? null : CarbonImmutable::parse($at)->setTimezone(self::TIMEZONE)->format('Y-m-d H:i');

        return $this->summary($row) + [
            'channel' => $row->channel, 'requester_role' => $row->requester_role, 'requester_name' => $row->requester_name,
            'requester_contact' => $row->requester_contact, 'details' => $row->details,
            'passed_at' => $local($row->passed_at), 'answered_at' => $local($row->answered_at), 'closed_at' => $local($row->closed_at),
            'outcome_note' => $row->outcome_note, 'closing_note' => $row->closing_note,
            'logged_by' => $names[$row->logged_by] ?? null, 'passed_by' => $names[$row->passed_by] ?? null,
            'answered_by' => $names[$row->answered_by] ?? null, 'closed_by' => $names[$row->closed_by] ?? null,
        ];
    }

    /** @return array<int, string> */
    private function actorNames(array $ids): array
    {
        $ids = collect($ids)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        return $ids->isEmpty() ? [] : User::acrossSchools()->withTrashed()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name)])->all();
    }
}
