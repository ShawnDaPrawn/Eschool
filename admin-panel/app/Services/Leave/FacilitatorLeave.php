<?php

declare(strict_types=1);

namespace App\Services\Leave;

use App\Models\Academics\TimetableLesson;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivitySession;
use App\Models\Activities\ActivityStaff;
use App\Models\Leave\FacilitatorAbsence;
use App\Models\Leave\FacilitatorSessionCover;
use App\Models\LeaveDetail;
use App\Models\Pastoral\CoverAssignment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\TimetableEngine;
use App\Services\Activities\ActivityOperations;
use App\Services\Activities\CommunityAuthority;
use App\Services\Facilitators\FacilitatorAccess;
use App\Services\Panel\SessionYearContext;
use App\Services\Pastoral\CoverAvailability;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Files\FileNames;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Facilitator absences and dated activity cover never create teacher records or permanent group appointments. */
class FacilitatorLeave
{
    public function manager(User $actor, bool $write = false): User
    {
        $fresh = app(CommunityAuthority::class)->staff($actor, $write);
        $policy = app(ProfilePolicy::class);
        abort_unless($policy->permission($fresh, 'activity-groups-manage') && $policy->permission($fresh, 'leave-approve'), 403);
        abort_if(app(CurrentSchool::class)->isImpersonating(), 403);

        return $fresh;
    }

    public function canManage(User $actor): bool
    {
        try {
            $this->manager($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function owner(User $actor, bool $write = false): User
    {
        $fresh = app(FacilitatorAccess::class)->ready($actor);
        abort_unless(SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $fresh->id)->where('role', 'Facilitator')->exists(), 403);
        app(CommunityAuthority::class)->staff($fresh, $write);
        abort_if(app(CurrentSchool::class)->isImpersonating(), 403);

        return $fresh;
    }

    public function canRequest(User $actor): bool
    {
        try {
            $this->owner($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function actor(User $actor): User
    {
        return $this->canManage($actor) ? $this->manager($actor) : $this->owner($actor);
    }

    public function available(User $actor): bool
    {
        try {
            $this->actor($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    private function current(int $year): SessionYear
    {
        $context = app(SessionYearContext::class);
        abort_unless($year === $context->currentId() && $context->selectedId() === $context->currentId(), 423, 'Select the current school year to change facilitator leave.');

        return SessionYear::findOrFail($year);
    }

    public function record(User $actor, int $id): FacilitatorAbsence
    {
        $fresh = $this->actor($actor);
        $row = FacilitatorAbsence::findOrFail($id);
        abort_unless((int) $row->user_id === (int) $fresh->id || $this->canManage($fresh), 404);

        return $row;
    }

    public function listing(User $actor, int $year, string $status = '')
    {
        $fresh = $this->actor($actor);
        SessionYear::findOrFail($year);

        return FacilitatorAbsence::where('session_year_id', $year)
            ->when(! $this->canManage($fresh), fn ($q) => $q->where('user_id', $fresh->id))
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')->paginate(25)->withQueryString();
    }

    /** Portion boundaries are explicit: first half before 12:00, second half from 12:00. Untimed sessions need cover for either half. */
    public function overlapsPortion(ActivitySession $session, string $portion): bool
    {
        if ($portion === 'full' || ! $session->starts_at || ! $session->ends_at) {
            return true;
        }

        return $portion === 'first_half' ? substr((string) $session->starts_at, 0, 5) < '12:00' : substr((string) $session->ends_at, 0, 5) > '12:00';
    }

    private function activeGroups(int $year): array
    {
        $rows = ActivityGroup::where('session_year_id', $year)->limit(1001)->get()->keyBy('id');
        abort_if($rows->count() > 1000, 422, 'Narrow the activity year before reviewing leave.');

        return $rows->filter(function ($group) use ($rows) {
            $seen = [];
            $node = $group;
            while ($node) {
                if (! $node->active || in_array((int) $node->id, $seen, true) || count($seen) >= 12) {
                    return false;
                }
                $seen[] = (int) $node->id;
                if ($node->parent_id === null) {
                    return true;
                }
                $node = $rows->get($node->parent_id);
            }

            return false;
        })->keys()->map(fn ($id) => (int) $id)->all();
    }

    /** Only the person's original, active dated facilitator appointments can be affected. */
    public function affected(int $userId, int $year, array $portions): Collection
    {
        if ($portions === []) {
            return collect();
        }
        $groups = $this->activeGroups($year);
        $allocations = ActivityStaff::where('user_id', $userId)->where('active', true)->where('role', 'facilitator')->whereIn('activity_group_id', $groups)->get();

        return ActivitySession::whereIn('activity_group_id', $allocations->pluck('activity_group_id'))->whereIn('on_date', array_keys($portions))
            ->where('status', '!=', 'cancelled')->orderBy('on_date')->orderBy('starts_at')->get()
            ->filter(function ($session) use ($allocations, $portions) {
                $date = $session->on_date->toDateString();

                return $allocations->contains(fn ($allocation) => (int) $allocation->activity_group_id === (int) $session->activity_group_id && $allocation->starts_on->toDateString() <= $date && $allocation->ends_on->toDateString() >= $date)
                    && $this->overlapsPortion($session, $portions[$date]);
            })->values();
    }

    public function request(User $actor, array $input, array $files = []): FacilitatorAbsence
    {
        $fresh = $this->owner($actor, true);
        $data = Validator::make($input, [
            'operation_id' => ['required', 'uuid'], 'session_year_id' => ['required', 'integer', 'min:1'],
            'from_date' => ['required', 'date_format:Y-m-d'], 'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'], 'portions' => ['required', 'array', 'min:1', 'max:366'],
            'portions.*' => ['required', Rule::in(['full', 'first_half', 'second_half'])],
        ])->validate();
        Validator::make(['files' => $files], ['files' => ['array', 'max:5'], 'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,txt']])->validate();
        $data['attachment_hashes'] = array_map(fn (UploadedFile $file) => hash_file('sha256', $file->getRealPath()), $files);
        $paths = [];
        try {
            $result = app(ActivityOperations::class)->once($fresh, 'facilitator-leave:new', $data, function () use ($fresh, $data) {
                $this->owner($fresh, true);
                $this->current((int) $data['session_year_id']);
            }, function () use ($fresh, $data, $files, &$paths) {
                $year = $this->current((int) $data['session_year_id']);
                $from = CarbonImmutable::parse($data['from_date']);
                $to = CarbonImmutable::parse($data['to_date']);
                abort_if($from->lt(CarbonImmutable::parse($year->start_date)) || $to->gt(CarbonImmutable::parse($year->end_date)) || $from->diffInDays($to) > 365, 422, 'Request dates must be within the current school year.');
                $dates = [];
                for ($day = $from; $day->lte($to); $day = $day->addDay()) {
                    $dates[] = $day->toDateString();
                }
                $keys = array_keys($data['portions']);
                sort($keys);
                abort_unless($keys === $dates, 422, 'Choose exactly one day portion for every requested date.');
                abort_if($this->affected((int) $fresh->id, (int) $year->id, $data['portions'])->isEmpty(), 422, 'No dated facilitator activity sessions are affected by this request.');
                foreach (FacilitatorAbsence::where('user_id', $fresh->id)->whereIn('status', ['pending', 'approved'])->whereDate('from_date', '<=', $data['to_date'])->whereDate('to_date', '>=', $data['from_date'])->get() as $existing) {
                    foreach ($data['portions'] as $date => $portion) {
                        $previous = $existing->portions[$date] ?? null;
                        abort_if($previous !== null && ($portion === 'full' || $previous === 'full' || $portion === $previous), 409, 'A pending or approved request already covers one of these day portions.');
                    }
                }
                $attachments = [];
                foreach ($files as $file) {
                    $path = $file->store((string) app(CurrentSchool::class)->id(), 'leave_private');
                    $paths[] = $path;
                    $attachments[] = ['path' => $path, 'name' => FileNames::forUpload($file), 'size' => $file->getSize()];
                }
                $row = FacilitatorAbsence::create(['user_id' => $fresh->id, 'session_year_id' => $year->id, 'from_date' => $data['from_date'], 'to_date' => $data['to_date'], 'portions' => $data['portions'], 'reason' => $data['reason'], 'attachments' => $attachments, 'status' => 'pending', 'revision' => 1]);
                Audit::record('facilitator_leave.requested', $row, [], ['status' => 'pending', 'user_id' => $fresh->id, 'from_date' => $data['from_date'], 'to_date' => $data['to_date']]);

                return ['id' => (int) $row->id];
            });
        } catch (\Throwable $error) {
            foreach ($paths as $path) {
                Storage::disk('leave_private')->delete($path);
            }
            throw $error;
        }

        return $this->record($fresh, $result['id']);
    }

    public function decide(User $actor, int $id, array $input): FacilitatorAbsence
    {
        $this->manager($actor, true);
        $this->record($actor, $id);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'decision' => ['required', Rule::in(['approved', 'rejected'])]])->validate();
        app(ActivityOperations::class)->once($actor, 'facilitator-leave:decision:'.$id, $data, function () use ($actor, $id) {
            $this->manager($actor, true);
            $this->current((int) $this->record($actor, $id)->session_year_id);
        }, function () use ($actor, $id, $data) {
            $row = FacilitatorAbsence::lockForUpdate()->findOrFail($id);
            $this->current((int) $row->session_year_id);
            abort_unless($row->revision === (int) $data['revision'] && $row->status === 'pending', 409, 'This request changed or is already finalised. Review the current request.');
            if ($data['decision'] === 'approved') {
                $this->owner(User::findOrFail($row->user_id));
                abort_if($this->affected((int) $row->user_id, (int) $row->session_year_id, $row->portions)->isEmpty(), 409, 'Dated activity assignments changed. There are no eligible affected sessions.');
            }
            $row->fill(['status' => $data['decision'], 'decided_by' => $actor->id, 'decided_at' => now(), 'revision' => $row->revision + 1])->save();
            Audit::record('facilitator_leave.decided', $row, ['status' => 'pending'], ['status' => $row->status, 'by' => $actor->id]);

            return ['id' => (int) $row->id];
        });

        return $this->record($actor, $id);
    }

    private function timeOverlap($left, ActivitySession $right): bool
    {
        return ! $left->starts_at || ! $left->ends_at || ! $right->starts_at || ! $right->ends_at
            || (substr((string) $left->starts_at, 0, 5) < substr((string) $right->ends_at, 0, 5) && substr((string) $left->ends_at, 0, 5) > substr((string) $right->starts_at, 0, 5));
    }

    private function eligible(User $person, FacilitatorAbsence $absence, ActivitySession $session, ?int $coverId = null): bool
    {
        try {
            $this->owner($person);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
        if ((int) $person->id === (int) $absence->user_id) {
            return false;
        }
        $date = $session->on_date->toDateString();
        $allocations = ActivityStaff::where('user_id', $person->id)->whereIn('activity_group_id', $this->activeGroups((int) $absence->session_year_id))->where('active', true)->where('role', 'facilitator')->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->get();
        if ($allocations->isEmpty()) {
            return false;
        }
        foreach (FacilitatorAbsence::where('user_id', $person->id)->whereIn('status', ['pending', 'approved'])->whereDate('from_date', '<=', $date)->whereDate('to_date', '>=', $date)->get() as $other) {
            if (isset($other->portions[$date]) && $this->overlapsPortion($session, $other->portions[$date])) {
                return false;
            }
        }
        // Consider every approved day portion; one request must not mask another half-day.
        $legacyPortions = LeaveDetail::whereDate('date', $date)
            ->whereHas('leave', fn ($query) => $query->where('user_id', $person->id)->where('status', 1))->pluck('type');
        foreach ($legacyPortions as $legacyPortion) {
            $portion = match ($legacyPortion) {
                'First Half' => 'first_half', 'Second Half' => 'second_half', default => 'full',
            };
            if ($this->overlapsPortion($session, $portion)) {
                return false;
            }
        }
        if ($teacher = Teacher::where('user_id', $person->id)->first()) {
            if (app(CoverAvailability::class)->hasUnmapped((int) $teacher->id, (int) $absence->session_year_id, $date, $date)) {
                return false;
            }
            foreach (TimetableLesson::with('period')->where('teacher_id', $teacher->id)->where('active', true)->where('session_year_id', $absence->session_year_id)->get() as $lesson) {
                if (in_array($date, app(TimetableEngine::class)->dates($lesson->getAttributes()), true) && $lesson->period && $this->timeOverlap($lesson->period, $session)) {
                    return false;
                }
            }
            foreach (CoverAssignment::where('substitute_teacher_id', $teacher->id)->where('state', 'assigned')->whereDate('on_date', $date)->get() as $assignment) {
                $times = (object) ['starts_at' => $assignment->source_snapshot['starts_at'] ?? null, 'ends_at' => $assignment->source_snapshot['ends_at'] ?? null];
                if ($this->timeOverlap($times, $session)) {
                    return false;
                }
            }
        }

        foreach (ActivitySession::whereIn('activity_group_id', $allocations->pluck('activity_group_id'))->whereDate('on_date', $date)->where('status', '!=', 'cancelled')->where('id', '!=', $session->id)->get() as $other) {
            if ($this->timeOverlap($other, $session)) {
                return false;
            }
        }
        foreach (FacilitatorSessionCover::where('replacement_user_id', $person->id)->whereDate('on_date', $date)->when($coverId, fn ($q) => $q->where('id', '!=', $coverId))->get() as $other) {
            if ($this->timeOverlap($other, $session)) {
                return false;
            }
        }

        return true;
    }

    public function candidates(User $actor, FacilitatorAbsence $absence, ActivitySession $session): Collection
    {
        $this->manager($actor);
        abort_unless($this->affected((int) $absence->user_id, (int) $absence->session_year_id, $absence->portions)->contains('id', $session->id), 404);
        $coverId = FacilitatorSessionCover::where('facilitator_absence_id', $absence->id)->where('activity_session_id', $session->id)->value('id');
        $ids = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('role', 'Facilitator')->pluck('user_id');

        return User::whereIn('id', $ids)->where('status', 1)->orderBy('first_name')->get()->filter(fn ($person) => $this->eligible($person, $absence, $session, $coverId))->values();
    }

    public function assign(User $actor, int $absenceId, int $sessionId, array $input): FacilitatorSessionCover
    {
        $this->manager($actor, true);
        $this->record($actor, $absenceId);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'session_revision' => ['required', 'integer', 'min:1'], 'cover_revision' => ['required', 'integer', 'min:0'], 'replacement_user_id' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'min:3', 'max:2000']])->validate();
        $result = app(ActivityOperations::class)->once($actor, 'facilitator-cover:'.$absenceId.':'.$sessionId, $data, function () use ($actor, $absenceId) {
            $this->manager($actor, true);
            $this->current((int) $this->record($actor, $absenceId)->session_year_id);
        }, function () use ($actor, $absenceId, $sessionId, $data) {
            $absence = FacilitatorAbsence::lockForUpdate()->findOrFail($absenceId);
            $this->current((int) $absence->session_year_id);
            abort_unless($absence->status === 'approved', 409, 'Approve the facilitator leave request before assigning session cover.');
            $session = ActivitySession::lockForUpdate()->findOrFail($sessionId);
            abort_unless($session->revision === (int) $data['session_revision'] && $session->status === 'scheduled', 409, 'The session changed or is no longer scheduled. Review it before assigning cover.');
            abort_if($session->on_date->toDateString() < app(CommunityAuthority::class)->today(), 422, 'Past activity sessions cannot receive a new cover assignment.');
            abort_unless($this->affected((int) $absence->user_id, (int) $absence->session_year_id, $absence->portions)->contains('id', $sessionId), 409, 'The original facilitator is no longer allocated to this dated session.');
            $cover = FacilitatorSessionCover::where('facilitator_absence_id', $absenceId)->where('activity_session_id', $sessionId)->first();
            abort_unless((int) ($cover?->revision ?? 0) === (int) $data['cover_revision'], 409, 'Session cover changed. Review the saved assignment.');
            $replacement = User::findOrFail($data['replacement_user_id']);
            abort_unless($this->eligible($replacement, $absence, $session, $cover?->id), 422, 'This facilitator is unavailable for the dated session. Choose another eligible replacement.');
            $before = $cover?->only(['replacement_user_id', 'revision', 'session_revision']) ?? [];
            $cover ??= new FacilitatorSessionCover(['facilitator_absence_id' => $absenceId, 'activity_session_id' => $sessionId]);
            $cover->fill(['activity_group_id' => $session->activity_group_id, 'replacement_user_id' => $replacement->id, 'assigned_by' => $actor->id, 'on_date' => $session->on_date, 'starts_at' => $session->starts_at, 'ends_at' => $session->ends_at, 'session_revision' => $session->revision, 'reason' => $data['reason'], 'revision' => (int) ($cover->revision ?? 0) + 1])->save();
            Audit::record('facilitator_leave.cover_assigned', $cover, $before, $cover->only(['replacement_user_id', 'revision', 'session_revision']));

            return ['id' => (int) $cover->id, 'activity_group_id' => (int) $session->activity_group_id];
        });

        return FacilitatorSessionCover::findOrFail($result['id']);
    }
}
