<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Models\User;
use App\Models\Wellbeing\Appointment;
use App\Models\Wellbeing\CareCase;
use App\Models\Wellbeing\CaseAssignment;
use App\Models\Wellbeing\CaseEntry;
use App\Models\Wellbeing\IncidentReport;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Casework
{
    public function __construct(private WellbeingAccess $access, private WellbeingOperations $operations) {}

    public function read(User $actor, int $id, ?int $before = null): array
    {
        $row = $this->access->case($actor, $id);
        if ($before !== null) {
            Validator::make(['before' => $before], ['before' => ['integer', 'min:1']])->validate();
        }
        $entries = CaseEntry::where('support_case_id', $id)->when($before !== null, fn ($q) => $q->where('id', '<', $before))->orderByDesc('id')->limit(101)->get();
        $more = $entries->count() > 100;
        $entries = $entries->take(100);

        $metadata = $row->only(['id', 'learner_id', 'status', 'opened_at', 'revision']);
        $metadata['incident_report_id'] = $row->incident_report_id !== null && IncidentReport::whereIn('category_id', $this->access->teamIds($actor))->whereKey($row->incident_report_id)->exists() ? (int) $row->incident_report_id : null;

        return $metadata + ['entries' => $entries->map(fn ($entry) => $entry->only(['id', 'author_id', 'supersedes_id', 'kind', 'body', 'happened_at', 'created_at']))->all(), 'has_older' => $more, 'next_before' => $more ? $entries->last()?->id : null];
    }

    public function listing(User $actor, int $page = 1): array
    {
        $ids = $this->access->caseIds($actor);
        Validator::make(['page' => $page], ['page' => ['integer', 'between:1,10000']])->validate();
        $rows = CareCase::whereIn('id', $ids)->orderByDesc('id')->paginate(30, ['id', 'learner_id', 'status', 'opened_at', 'revision'], 'page', $page);

        return ['items' => $rows->items(), 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
    }

    public function entry(User $actor, int $id, array $input): array
    {
        $this->access->case($actor, $id, true);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'kind' => ['required', Rule::in(['note', 'escalation'])], 'body' => ['required', 'string', 'max:20000'], 'happened_at' => ['required', 'date', 'before_or_equal:now'], 'supersedes_id' => ['nullable', 'integer', 'min:1']])->validate();

        return $this->operations->once($actor, 'case-entry:'.$id, $data, fn () => $this->access->case($actor, $id, true), function () use ($actor, $id, $data) {
            $case = $this->access->case($actor, $id, true);
            $this->operations->revision($case, $data);
            if (isset($data['supersedes_id'])) {
                // Corrections preserve the original and require its named author.
                CaseEntry::where('support_case_id', $id)->where('author_id', $actor->id)->findOrFail($data['supersedes_id']);
            }
            $entry = CaseEntry::create(['support_case_id' => $id, 'author_id' => $actor->id, 'kind' => $data['kind'], 'body' => $data['body'], 'happened_at' => CarbonImmutable::parse($data['happened_at'])->utc(), 'supersedes_id' => $data['supersedes_id'] ?? null]);
            $case->increment('revision');
            $this->operations->event($actor, 'wellbeing.case_entry_added', $entry);

            return ['case_revision' => $case->revision, 'entry' => $entry->only(['id', 'author_id', 'supersedes_id', 'kind', 'body', 'happened_at', 'created_at'])];
        });
    }

    public function linkIncident(User $actor, int $id, array $input): array
    {
        $this->access->case($actor, $id, true);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'incident_report_id' => ['required', 'integer', 'min:1']])->validate();
        $authority = function () use ($actor, $id, $data) {
            $case = $this->access->case($actor, $id, true);
            $report = $this->access->report($actor, $data['incident_report_id'], true);
            // Linking must not silently identify an anonymous reporter or assign
            // an incident about one learner to another learner's clinical case.
            abort_if($report->anonymous || $report->learner_id === null || (int) $report->learner_id !== (int) $case->learner_id, 422, 'Link only an explicitly identified report for this learner.');
        };

        return $this->operations->once($actor, 'case-link:'.$id, $data, $authority, function () use ($actor, $id, $data) {
            $case = $this->access->case($actor, $id, true);
            $this->operations->revision($case, $data);
            $case->fill(['incident_report_id' => $data['incident_report_id'], 'revision' => $case->revision + 1])->save();
            $this->operations->event($actor, 'wellbeing.case_incident_linked', $case);

            return $case->only(['id', 'learner_id', 'incident_report_id', 'status', 'revision']);
        });
    }

    public function status(User $actor, int $id, array $input): array
    {
        $this->access->case($actor, $id, true);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'status' => ['required', Rule::in(['open', 'closed'])], 'reason' => ['required', 'string', 'max:2000']])->validate();

        return $this->operations->once($actor, 'case-status:'.$id, $data, fn () => $this->access->case($actor, $id, true), function () use ($actor, $id, $data) {
            $case = $this->access->case($actor, $id, true);
            $this->operations->revision($case, $data);
            CaseEntry::create(['support_case_id' => $id, 'author_id' => $actor->id, 'kind' => 'status', 'body' => $data['reason'], 'happened_at' => now()]);
            $case->fill(['status' => $data['status'], 'revision' => $case->revision + 1])->save();
            $this->operations->event($actor, 'wellbeing.case_status_changed', $case);

            return $case->only(['id', 'learner_id', 'status', 'revision']);
        });
    }

    public function appointment(User $actor, int $caseId, ?int $id, array $input): array
    {
        $this->access->case($actor, $caseId, true);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'professional_user_id' => ['required', 'integer', 'min:1'], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'], 'location' => ['required', 'string', 'max:500'], 'notes' => ['present', 'string', 'max:20000'], 'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])]])->validate();
        $start = CarbonImmutable::parse($data['starts_at'])->utc();
        $end = CarbonImmutable::parse($data['ends_at'])->utc();
        abort_if($end->diffInMinutes($start, true) > 480, 422, 'Keep an appointment within eight hours.');
        $authority = function () use ($actor, $caseId, $id, $data) {
            $this->access->case($actor, $caseId, true);
            $existing = $id === null ? null : Appointment::where('support_case_id', $caseId)->findOrFail($id);
            if ($data['status'] === 'cancelled') {
                abort_unless($existing !== null && (int) $existing->professional_user_id === (int) $data['professional_user_id'], 422, 'Cancel the saved appointment without changing its professional.');
            }
            if ($data['status'] !== 'cancelled') {
                $this->access->professional(User::findOrFail($data['professional_user_id']));
            }
        };

        return $this->operations->once($actor, 'appointment:'.$caseId.':'.($id ?? 'new'), $data, $authority, function () use ($actor, $caseId, $id, $data, $start, $end) {
            if ($data['status'] !== 'cancelled') {
                $zone = app(CurrentSchool::class)->school()->timezone;
                $this->access->professionalDuring(User::findOrFail($data['professional_user_id']), $start->setTimezone($zone)->toDateString(), $end->setTimezone($zone)->toDateString());
                abort_unless(CaseAssignment::where('support_case_id', $caseId)->where('user_id', $data['professional_user_id'])->where('active', true)->whereDate('starts_on', '<=', $start->setTimezone($zone)->toDateString())->whereDate('ends_on', '>=', $end->setTimezone($zone)->toDateString())->exists(), 422, 'The professional must be assigned throughout the appointment.');
                abort_if(Appointment::where('status', '!=', 'cancelled')->when($id !== null, fn ($q) => $q->where('id', '!=', $id))
                    ->where(fn ($q) => $q->where('professional_user_id', $data['professional_user_id'])->orWhere('support_case_id', $caseId))
                    ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists(), 409, 'This learner or professional already has an overlapping appointment.');
            }
            $row = $id === null ? new Appointment : Appointment::where('support_case_id', $caseId)->findOrFail($id);
            $this->operations->revision($row, $data);
            $row->fill(['support_case_id' => $caseId, 'professional_user_id' => $data['professional_user_id'], 'starts_at' => $start, 'ends_at' => $end, 'location' => $data['location'], 'notes' => $data['notes'], 'status' => $data['status'], 'revision' => $data['revision'] + 1])->save();
            $this->operations->event($actor, 'wellbeing.appointment_saved', $row);

            return $row->only(['id', 'support_case_id', 'professional_user_id', 'starts_at', 'ends_at', 'location', 'notes', 'status', 'revision']);
        });
    }

    public function appointments(User $actor, int $caseId, string $from, string $to): array
    {
        $this->access->case($actor, $caseId);
        Validator::make(['from' => $from, 'to' => $to], ['from' => ['date_format:Y-m-d'], 'to' => ['date_format:Y-m-d', 'after_or_equal:from']])->validate();
        abort_if(CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > 31, 422, 'Choose a calendar window of up to 31 days.');
        $zone = app(CurrentSchool::class)->school()->timezone;

        return Appointment::where('support_case_id', $caseId)->where('starts_at', '<', CarbonImmutable::parse($to, $zone)->addDay()->utc())
            ->where('ends_at', '>', CarbonImmutable::parse($from, $zone)->utc())->orderBy('starts_at')->limit(1000)->get()->map(fn ($row) => $row->only(['id', 'support_case_id', 'professional_user_id', 'starts_at', 'ends_at', 'location', 'notes', 'status', 'revision']))->all();
    }
}
