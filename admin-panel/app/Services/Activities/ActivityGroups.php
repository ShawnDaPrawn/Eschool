<?php

declare(strict_types=1);

namespace App\Services\Activities;

use App\Models\Activities\ActivityAttendance;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMaterial;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivitySession;
use App\Models\Activities\ActivityStaff;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\Content\ContentMarkdown;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ActivityGroups
{
    public function __construct(private CommunityAuthority $access, private ActivityOperations $operations) {}

    public static function rules(string $action): array
    {
        $base = ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0']];
        $dates = ['starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on']];
        $reason = ['reason' => ['required', 'string', 'min:3', 'max:2000']];

        return match ($action) {
            'create','save' => $base + $dates + ['session_year_id' => ['required', 'integer', 'min:1'], 'parent_id' => ['nullable', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:191'], 'discipline' => ['required', 'string', 'max:100'], 'season' => ['required', 'string', 'max:100'],
                'gender' => ['required', Rule::in(['any', 'male', 'female'])], 'minimum_grade' => ['nullable', 'integer', 'between:0,12'], 'maximum_grade' => ['nullable', 'integer', 'between:0,12'],
                'minimum_age' => ['nullable', 'integer', 'between:0,25'], 'maximum_age' => ['nullable', 'integer', 'between:0,25'], 'active' => ['required', 'boolean']],
            'membership-save' => $base + $dates + $reason + ['active' => ['sometimes', 'boolean'], 'learner_id' => ['required', 'integer', 'min:1'], 'role' => ['required', Rule::in(['member', 'captain'])]],
            'staff-save' => $base + $dates + $reason + ['active' => ['sometimes', 'boolean'], 'user_id' => ['required', 'integer', 'min:1'], 'role' => ['required', Rule::in(['admin', 'facilitator'])], 'cascade' => ['required', 'boolean']],
            'session-save' => $base + ['on_date' => ['required', 'date_format:Y-m-d'], 'name' => ['required', 'string', 'max:191'], 'venue' => ['required', 'string', 'max:191'], 'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])]],
            'attendance-save' => $base + $reason + ['entries' => ['required', 'array', 'min:1', 'max:500'], 'entries.*' => ['required', 'array:learner_id,status,revision'], 'entries.*.learner_id' => ['required', 'integer', 'min:1', 'distinct'], 'entries.*.status' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])], 'entries.*.revision' => ['required', 'integer', 'min:0']],
            'roster' => ['session_id' => ['sometimes', 'integer', 'min:1']],
            'material-save' => $base + ['title' => ['required', 'string', 'max:191'], 'markdown' => ['required', 'string', 'max:20000'], 'published' => ['required', 'boolean']],
            default => ['page' => ['sometimes', 'integer', 'between:1,10000'], 'session_year_id' => ['sometimes', 'integer', 'min:1']],
        };
    }

    private function present(Model $row): array
    {
        $fields = match ($row->getTable()) {
            'activity_groups' => ['id', 'school_id', 'session_year_id', 'parent_id', 'name', 'discipline', 'season', 'starts_on', 'ends_on', 'gender', 'minimum_grade', 'maximum_grade', 'minimum_age', 'maximum_age', 'active', 'revision'],
            'activity_group_memberships' => ['id', 'activity_group_id', 'learner_id', 'role', 'active', 'starts_on', 'ends_on', 'reason', 'revision'],
            'activity_group_staff' => ['id', 'activity_group_id', 'user_id', 'role', 'cascade', 'active', 'starts_on', 'ends_on', 'reason', 'revision'],
            'activity_sessions' => ['id', 'activity_group_id', 'on_date', 'name', 'venue', 'status', 'revision'],
            'activity_attendance' => ['id', 'activity_session_id', 'learner_id', 'status', 'recorded_by', 'reason', 'revision'],
            'activity_materials' => ['id', 'activity_group_id', 'author_id', 'title', 'markdown', 'published', 'revision'],
        };
        $out = $row->only($fields);
        foreach ($out as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $out[$key] = $value->format('Y-m-d');
            } elseif ($value !== null && ($key === 'id' || str_ends_with($key, '_id') || in_array($key, ['revision', 'minimum_grade', 'maximum_grade', 'minimum_age', 'maximum_age'], true))) {
                $out[$key] = (int) $value;
            }
        }

        return $out;
    }

    private function revision(Model $row, array $data): void
    {
        abort_unless((int) ($row->revision ?? 0) === (int) $data['revision'], 409, 'This activity record changed. Refresh before saving.');
    }

    private function audit(string $event, Model $row, array $before): array
    {
        $after = $this->present($row);
        // Sensitive notes/material bodies remain encrypted on records, not in audit JSON.
        foreach (['reason', 'markdown'] as $key) {
            if (isset($before[$key])) {
                $before[$key.'_sha256'] = hash('sha256', $before[$key]);
            }
            unset($before[$key]);
        }
        $public = $after;
        foreach (['reason', 'markdown'] as $key) {
            if (isset($public[$key])) {
                $public[$key.'_sha256'] = hash('sha256', $public[$key]);
            }
            unset($public[$key]);
        }
        Audit::record($event, $row, $before, $public);

        return $after;
    }

    private function inSeason(ActivityGroup $group, string $start, string $end): void
    {
        abort_if($start < $group->starts_on->toDateString() || $end > $group->ends_on->toDateString(), 422, 'Dates must be within the activity season.');
    }

    public function save(User $actor, ?int $id, array $input): array
    {
        $this->access->manage($actor);
        $data = Validator::make($input, self::rules($id === null ? 'create' : 'save'))->validate();

        return $this->operations->once($actor, 'group:'.($id ?? 'new'), $data, fn () => $this->access->manage($actor), function () use ($id, $data) {
            $year = SessionYear::findOrFail($data['session_year_id']);
            abort_if($data['starts_on'] < CarbonImmutable::parse($year->start_date)->toDateString() || $data['ends_on'] > CarbonImmutable::parse($year->end_date)->toDateString(), 422, 'Season must be within the school year.');
            foreach (['grade', 'age'] as $kind) {
                $min = $data['minimum_'.$kind] ?? null;
                $max = $data['maximum_'.$kind] ?? null;
                abort_if($min !== null && $max !== null && $min > $max, 422, 'Minimum eligibility exceeds maximum.');
            }
            $row = $id === null ? new ActivityGroup : ActivityGroup::findOrFail($id);
            $this->revision($row, $data);
            $before = $row->exists ? $this->present($row) : [];
            if ($row->exists) {
                abort_unless((int) $row->session_year_id === (int) $year->id, 409, 'Create a new group for another year.');
            }
            if (($data['parent_id'] ?? null) !== null) {
                $parent = ActivityGroup::where('session_year_id', $year->id)->where('active', true)->findOrFail($data['parent_id']);
                abort_if($id !== null && in_array($id, $this->access->lineage($parent), true), 422, 'A group cannot contain its ancestor.');
                $subtreeHeight = $row->exists ? $this->access->subtreeHeight($row) : 1;
                abort_if(count($this->access->lineage($parent)) + $subtreeHeight > 12, 422, 'Activity tree exceeds twelve levels.');
                $this->inSeason($parent, $data['starts_on'], $data['ends_on']);
            }
            if ($row->exists) {
                if (ActivityMembership::where('activity_group_id', $id)->exists()) {
                    foreach (['gender', 'minimum_grade', 'maximum_grade', 'minimum_age', 'maximum_age'] as $field) {
                        abort_if(($row->$field === null ? null : (string) $row->$field) !== (($data[$field] ?? null) === null ? null : (string) $data[$field]), 409, 'Create a new group to change eligibility after admitting members.');
                    }
                }
                abort_if(ActivitySession::where('activity_group_id', $id)->where(fn ($q) => $q->whereDate('on_date', '<', $data['starts_on'])->orWhereDate('on_date', '>', $data['ends_on']))->exists(), 422, 'Season must contain retained session dates.');
                abort_if(! $data['active'] && ActivityGroup::where('parent_id', $id)->where('active', true)->exists(), 422, 'Archive active children first.');
                foreach (ActivityGroup::where('parent_id', $id)->get() as $child) {
                    abort_if($child->starts_on->toDateString() < $data['starts_on'] || $child->ends_on->toDateString() > $data['ends_on'], 422, 'Season must contain all child seasons.');
                }
                foreach ([ActivityMembership::class, ActivityStaff::class] as $type) {
                    abort_if($type::where('activity_group_id', $id)->where(fn ($q) => $q->whereDate('starts_on', '<', $data['starts_on'])->orWhereDate('ends_on', '>', $data['ends_on']))->exists(), 422, 'Season must contain retained allocation dates.');
                }
            }
            $values = $data;
            unset($values['operation_id']);
            $values['parent_id'] = $values['parent_id'] ?? null;
            foreach (['minimum_grade', 'maximum_grade', 'minimum_age', 'maximum_age'] as $field) {
                $values[$field] = $values[$field] ?? null;
            }
            $values['revision'] = (int) $data['revision'] + 1;
            $row->fill($values)->save();

            return $this->audit('activity.group_saved', $row, $before);
        });
    }

    private function eligible(ActivityGroup $group, Students $learner, array $data): void
    {
        $start = $data['starts_on'];
        $end = $data['ends_on'];
        $enrolment = Enrolment::where('learner_id', $learner->id)->where('session_year_id', $group->session_year_id)->whereDate('starts_on', '<=', $start)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $end))->firstOrFail();
        $user = User::where('status', 1)->findOrFail($learner->user_id);
        abort_unless(SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $user->id)->where('role', 'Student')->exists(), 422, 'Learner has no active school membership.');
        if ($group->gender !== 'any') {
            abort_unless(strtolower((string) $user->gender) === $group->gender, 422, 'Learner does not meet the group gender rule.');
        }
        if ($group->minimum_age !== null || $group->maximum_age !== null) {
            abort_if($user->dob === null, 422, 'A date of birth is required by this group.');
            abort_if(CarbonImmutable::parse($user->dob)->toDateString() > $start, 422, 'Date of birth cannot follow membership start.');
            $age = (int) CarbonImmutable::parse($user->dob)->diffInYears(CarbonImmutable::parse($start));
            abort_if(($group->minimum_age !== null && $age < $group->minimum_age) || ($group->maximum_age !== null && $age > $group->maximum_age), 422, 'Learner does not meet the age rule.');
        }
        if ($group->minimum_grade !== null || $group->maximum_grade !== null) {
            $code = $enrolment->homeroom?->class?->gradeLevel?->code;
            abort_if($code === null, 422, 'An explicit class grade is required.');
            $grade = $code === 'R' ? 0 : (int) $code;
            abort_if(($group->minimum_grade !== null && $grade < $group->minimum_grade) || ($group->maximum_grade !== null && $grade > $group->maximum_grade), 422, 'Learner does not meet the grade rule.');
        }
    }

    public function allocation(User $actor, int $groupId, ?int $id, bool $staff, array $input): array
    {
        $this->access->manage($actor);
        $action = $staff ? 'staff-save' : 'membership-save';
        $data = Validator::make($input, self::rules($action))->validate();

        return $this->operations->once($actor, $action.':'.$groupId.':'.($id ?? 'new'), $data, fn () => $this->access->manage($actor), function () use ($groupId, $id, $staff, $data) {
            $group = ActivityGroup::where('active', true)->findOrFail($groupId);
            $this->inSeason($group, $data['starts_on'], $data['ends_on']);
            $type = $staff ? ActivityStaff::class : ActivityMembership::class;
            $field = $staff ? 'user_id' : 'learner_id';
            $row = $id === null ? new $type : $type::where('activity_group_id', $groupId)->findOrFail($id);
            $this->revision($row, $data);
            if ($row->exists) {
                abort_unless((int) $row->$field === (int) $data[$field], 409, 'Create a new allocation for another person.');
            }
            $active = (bool) ($data['active'] ?? ($row->exists ? $row->active : true));
            abort_if(! $active && ! $row->exists, 422, 'Only an existing allocation can be cancelled.');
            if ($staff && $active) {
                $user = User::where('status', 1)->findOrFail($data['user_id']);
                $roles = app(ProfilePolicy::class)->roles($user);
                abort_if(array_intersect($roles, ['Teacher', 'Facilitator']) === [], 422, 'Assign a teacher or facilitator school membership first.');
                abort_if($data['cascade'] && $data['role'] !== 'admin', 422, 'Only group administrators can hold a subtree grant.');
            } elseif (! $staff && $active) {
                $this->eligible($group, Students::findOrFail($data['learner_id']), $data);
            }
            abort_if($active && $type::where('active', true)->where('activity_group_id', $groupId)->where($field, $data[$field])->when($id !== null, fn ($q) => $q->whereKeyNot($id))
                ->whereDate('starts_on', '<=', $data['ends_on'])->whereDate('ends_on', '>=', $data['starts_on'])->exists(), 409, 'Allocation dates overlap.');
            $before = $row->exists ? $this->present($row) : [];
            $values = $data;
            unset($values['operation_id']);
            $values['activity_group_id'] = $groupId;
            $values['active'] = $active;
            $values['revision'] = $data['revision'] + 1;
            $row->fill($values)->save();

            return $this->audit('activity.allocation_saved', $row, $before);
        });
    }

    public function session(User $actor, int $groupId, ?int $id, array $input): array
    {
        $this->access->groupStaff($actor, $groupId, true);
        $data = Validator::make($input, self::rules('session-save'))->validate();

        return $this->operations->once($actor, 'session:'.$groupId.':'.($id ?? 'new'), $data, fn () => $this->access->groupStaff($actor, $groupId, true, $data['on_date']), function () use ($groupId, $id, $data) {
            $group = ActivityGroup::where('active', true)->findOrFail($groupId);
            $this->inSeason($group, $data['on_date'], $data['on_date']);
            $row = $id === null ? new ActivitySession : ActivitySession::where('activity_group_id', $groupId)->findOrFail($id);
            $this->revision($row, $data);
            if ($row->exists && $row->on_date->toDateString() !== $data['on_date']) {
                abort_if(ActivityAttendance::where('activity_session_id', $row->id)->exists(), 409, 'A captured session date cannot change.');
            }
            $before = $row->exists ? $this->present($row) : [];
            $values = $data;
            unset($values['operation_id']);
            $values['activity_group_id'] = $groupId;
            $values['revision'] = $data['revision'] + 1;
            $row->fill($values)->save();

            return $this->audit('activity.session_saved', $row, $before);
        });
    }

    public function attendance(User $actor, int $sessionId, array $input): array
    {
        $this->access->staff($actor, true);
        $session = ActivitySession::findOrFail($sessionId);
        $this->access->groupStaff($actor, (int) $session->activity_group_id, true, $session->on_date->toDateString());
        $data = Validator::make($input, self::rules('attendance-save'))->validate();

        return $this->operations->once($actor, 'attendance:'.$sessionId, $data, function () use ($actor, $sessionId) {
            $fresh = ActivitySession::findOrFail($sessionId);
            $this->access->groupStaff($actor, (int) $fresh->activity_group_id, true, $fresh->on_date->toDateString());
        }, function () use ($actor, $sessionId, $data) {
            $session = ActivitySession::findOrFail($sessionId);
            $this->revision($session, $data);
            abort_if($session->status === 'cancelled', 409, 'Cancelled sessions cannot capture attendance.');
            $date = $session->on_date->toDateString();
            if ($date > $this->access->today()) {
                throw ValidationException::withMessages(['on_date' => 'Session attendance opens on the session date.']);
            }
            $roster = $this->sessionLearnerIds($session);
            foreach ($data['entries'] as $entry) {
                $row = ActivityAttendance::firstOrNew(['activity_session_id' => $sessionId, 'learner_id' => $entry['learner_id']]);
                // Withdrawal closes future group access, not the ability to correct
                // a retained record already captured within this authorized session.
                abort_unless($row->exists || in_array((int) $entry['learner_id'], $roster, true), 422, 'Learner is outside the dated session roster.');
                Students::withTrashed()->findOrFail($entry['learner_id']);
                $this->revision($row, $entry);
                $before = $row->exists ? $this->present($row) : [];
                $row->fill(['status' => $entry['status'], 'recorded_by' => $actor->id, 'reason' => $data['reason'], 'revision' => $entry['revision'] + 1])->save();
                $this->audit('activity.attendance_saved', $row, $before);
            }
            $session->increment('revision');

            return ['session_revision' => $session->revision, 'items' => ActivityAttendance::where('activity_session_id', $sessionId)->get()->map(fn ($x) => $this->present($x))->all(), 'unrecorded_count' => count(array_diff($roster, ActivityAttendance::where('activity_session_id', $sessionId)->pluck('learner_id')->map(fn ($x) => (int) $x)->all()))];
        });
    }

    public function material(User $actor, int $groupId, ?int $id, array $input): array
    {
        $this->access->groupStaff($actor, $groupId, true);
        $data = Validator::make($input, self::rules('material-save'))->validate();
        $authority = function () use ($actor, $groupId, $id) {
            $this->access->groupStaff($actor, $groupId, true);
            if ($id !== null) {
                $row = ActivityMaterial::where('activity_group_id', $groupId)->findOrFail($id);
                abort_unless((int) $row->author_id === (int) $actor->id || $this->access->manages($actor), 404);
            }
        };

        return $this->operations->once($actor, 'material:'.$groupId.':'.($id ?? 'new'), $data, $authority, function () use ($actor, $groupId, $id, $data) {
            $row = $id === null ? new ActivityMaterial : ActivityMaterial::where('activity_group_id', $groupId)->findOrFail($id);
            $this->revision($row, $data);
            $before = $row->exists ? $this->present($row) : [];
            abort_if(strlen($data['markdown']) > 65536, 422, 'Keep the material within 64 KiB.');
            $markdown = app(ContentMarkdown::class)->clean($data['markdown']);
            $row->fill(['activity_group_id' => $groupId, 'author_id' => $row->author_id ?? $actor->id, 'title' => trim($data['title']), 'markdown' => $markdown, 'published' => $data['published'], 'revision' => $data['revision'] + 1])->save();

            return $this->audit('activity.material_saved', $row, $before);
        });
    }

    public function listing(User $actor, ?int $year = null, int $page = 1): array
    {
        $rows = $this->access->visible($actor, $year);
        $count = count($rows);

        return ['items' => array_map(fn ($row) => $this->present($row), array_slice($rows, ($page - 1) * 30, 30)), 'page' => $page, 'last_page' => max(1, (int) ceil($count / 30)), 'total' => $count];
    }

    public function detail(User $actor, int $id): array
    {
        $group = $this->access->read($actor, $id);
        $staff = $this->access->canStaff($actor, $id);
        $materials = ActivityMaterial::where('activity_group_id', $id)->when(! $staff, fn ($q) => $q->where('published', true))->orderBy('id')->get()->map(fn ($row) => $this->present($row) + ['html' => app(ContentMarkdown::class)->html($row->markdown)])->all();

        return ['group' => $this->present($group), 'materials' => $materials, 'sessions' => ActivitySession::where('activity_group_id', $id)->orderBy('on_date')->get()->map(fn ($x) => $this->present($x))->all(), 'can_manage' => $staff && $this->access->manages($actor), 'can_capture' => $staff && $group->active && app(ModuleGate::class)->writable('groups')];
    }

    public function roster(User $actor, int $id, ?int $sessionId = null): array
    {
        $this->access->groupStaff($actor, $id);

        $session = $sessionId === null ? null : ActivitySession::where('activity_group_id', $id)->findOrFail($sessionId);
        if ($session !== null) {
            $this->access->groupStaff($actor, $id, false, $session->on_date->toDateString());
        }

        $result = ['memberships' => ActivityMembership::where('activity_group_id', $id)->get()->map(function ($row) {
            $learner = Students::withTrashed()->findOrFail($row->learner_id);
            $user = User::find($learner->user_id);

            return $this->present($row) + ['learner_name' => $user === null ? 'Former learner' : trim($user->first_name.' '.$user->last_name)];
        })->all(),
            'staff' => ActivityStaff::where('activity_group_id', $id)->get()->map(fn ($row) => $this->present($row))->all(),
            'attendance' => ActivityAttendance::whereIn('activity_session_id', ActivitySession::where('activity_group_id', $id)->select('id'))->get()->map(fn ($row) => $this->present($row))->all()];
        if ($session !== null) {
            $captured = ActivityAttendance::where('activity_session_id', $session->id)->get()->keyBy('learner_id');
            $ids = array_values(array_unique([...$this->sessionLearnerIds($session), ...$captured->keys()->map(fn ($value) => (int) $value)->all()]));
            sort($ids);
            $result['session_roster'] = ['session_id' => (int) $session->id, 'group_id' => $id, 'session_revision' => (int) $session->revision,
                'learners' => array_map(function (int $learnerId) use ($captured) {
                    $learner = Students::withTrashed()->findOrFail($learnerId);
                    $user = User::find($learner->user_id);
                    $saved = $captured->get($learnerId);

                    return ['learner_id' => $learnerId, 'learner_name' => $user === null ? 'Former learner' : trim($user->first_name.' '.$user->last_name),
                        'status' => $saved?->status, 'revision' => $saved === null ? 0 : (int) $saved->revision];
                }, $ids)];
        }

        return $result;
    }

    /** The capture and read paths share exactly the same dated eligibility. */
    private function sessionLearnerIds(ActivitySession $session): array
    {
        $date = $session->on_date->toDateString();
        $group = ActivityGroup::findOrFail($session->activity_group_id);

        return ActivityMembership::where('active', true)->where('activity_group_id', $group->id)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)
            ->whereIn('learner_id', Enrolment::where('session_year_id', $group->session_year_id)->onDate($date)->select('learner_id'))
            ->pluck('learner_id')->map(fn ($value) => (int) $value)->all();
    }
}
