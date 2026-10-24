<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\AcademicAssignmentChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TeachingGroups
{
    public const ROWS = ['memberships' => TeachingGroupMembership::class, 'teachers' => TeachingAllocation::class,
        'homerooms' => HomeroomAllocation::class, 'leadership' => AcademicLeadership::class];

    public static function groupRules(): array
    {
        return ['name' => ['required', 'string', 'max:191'], 'subject_id' => ['required', 'integer', 'min:1'],
            'grade_level_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'],
            'class_section_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'active' => ['required', 'boolean'], 'revision' => ['sometimes', 'integer', 'min:1']];
    }

    public static function datedRules(string $kind): array
    {
        $rules = ['starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'reason' => ['required', 'string', 'max:500'], 'revision' => ['sometimes', 'integer', 'min:1']];

        return $rules + match ($kind) {
            'memberships' => ['learner_id' => ['required', 'integer', 'min:1'], 'mark_rule' => ['required', 'in:catch_up,excused,carried']],
            'teachers' => ['teacher_id' => ['required', 'integer', 'min:1']],
            'homerooms' => ['teacher_id' => ['required', 'integer', 'min:1'], 'class_section_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1']],
            'leadership' => ['user_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'], 'kind' => ['required', 'in:grade_head,principal'], 'grade_level_id' => ['required_if:kind,grade_head', 'nullable', 'integer', 'min:1']],
            default => throw new \InvalidArgumentException('Unknown academic allocation kind.'),
        };
    }

    public function save(User $actor, array $input, ?int $id = null): TeachingGroup
    {
        app(AcademicAuthority::class)->manage($actor);
        $data = Validator::make($input, self::groupRules())->validate();

        return DB::transaction(function () use ($actor, $data, $id) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(AcademicAuthority::class)->manage($actor);
            $subject = Subject::query()->findOrFail($data['subject_id']);
            $grade = GradeLevel::query()->findOrFail($data['grade_level_id']);
            $year = SessionYear::query()->findOrFail($data['session_year_id']);
            if (isset($data['class_section_id'])) {
                $this->room((int) $data['class_section_id'], (int) $grade->id);
            }
            $group = $id === null ? new TeachingGroup : TeachingGroup::query()->lockForUpdate()->findOrFail($id);
            $before = $group->exists ? $this->groupData($group) : [];
            if ($group->exists) {
                abort_if((int) ($data['revision'] ?? 0) !== $group->revision, 409, __('This group changed. Reload before saving.'));
                foreach (['subject_id', 'grade_level_id', 'session_year_id', 'class_section_id'] as $key) {
                    abort_if((string) $group->$key !== (string) ($data[$key] ?? null), 409, __('Create a new group to change its academic context.'));
                }
            }
            if (TeachingGroup::query()->where('subject_id', $subject->id)->where('grade_level_id', $grade->id)->where('session_year_id', $year->id)
                ->where('name', $data['name'])->when($id !== null, fn ($q) => $q->whereKeyNot($id))->exists()) {
                throw ValidationException::withMessages(['name' => __('A group with this name already exists in this subject, grade and year.')]);
            }
            $group->fill(array_intersect_key($data, array_flip(['name', 'subject_id', 'grade_level_id', 'session_year_id', 'class_section_id', 'active'])));
            if ($group->isDirty() || ! $group->exists) {
                if ($group->exists) {
                    $group->revision++;
                }
                $group->save();
                Audit::record('academics.teaching_group_saved', $group, $before, $this->groupData($group));
                Outbox::record(new AcademicAssignmentChanged(app(CurrentSchool::class)->id(), 'group', (int) $group->id, (int) $group->id));
                app(AcademicProjections::class)->refresh();
            }

            return $group;
        }, 3);
    }

    public function dated(User $actor, string $kind, array $input, ?int $groupId = null, ?int $id = null): Model
    {
        app(AcademicAuthority::class)->manage($actor);
        $data = Validator::make($input, self::datedRules($kind))->validate();
        if ($kind === 'leadership') {
            $data['grade_level_id'] ??= null;
        }

        return DB::transaction(function () use ($actor, $kind, $data, $groupId, $id) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(AcademicAuthority::class)->manage($actor);
            $group = $groupId === null ? null : TeachingGroup::query()->findOrFail($groupId);
            if (in_array($kind, ['memberships', 'teachers'], true)) {
                abort_if($group === null, 404);
            }
            $year = SessionYear::query()->findOrFail($group?->session_year_id ?? $data['session_year_id']);
            if ($data['starts_on'] < substr((string) $year->start_date, 0, 10) || $data['ends_on'] > substr((string) $year->end_date, 0, 10)) {
                throw ValidationException::withMessages(['starts_on' => __('Choose an effective interval within the school year.')]);
            }
            $row = array_diff_key($data, ['revision' => true]);
            if ($group !== null) {
                $row['teaching_group_id'] = $group->id;
            }
            if ($kind === 'memberships') {
                $this->learner($group, (int) $data['learner_id'], $data['starts_on'], $data['ends_on']);
            }
            if (in_array($kind, ['teachers', 'homerooms'], true)) {
                $this->teacher((int) $data['teacher_id']);
            }
            if ($kind === 'homerooms') {
                $this->room((int) $data['class_section_id']);
            }
            if ($kind === 'leadership') {
                $this->staffMember((int) $data['user_id']);
                if (($data['kind'] === 'principal') !== ($data['grade_level_id'] === null)) {
                    throw ValidationException::withMessages(['grade_level_id' => __('Choose a grade for a grade head; principals cover the school year.')]);
                }
                if ($data['grade_level_id'] !== null) {
                    GradeLevel::query()->findOrFail($data['grade_level_id']);
                }
            }
            $class = self::ROWS[$kind];
            $entry = $id === null ? new $class : $class::query()->when($groupId !== null, fn ($q) => $q->where('teaching_group_id', $groupId))->lockForUpdate()->findOrFail($id);
            $identity = match ($kind) {
                'memberships' => ['teaching_group_id', 'learner_id'], 'teachers' => ['teaching_group_id', 'teacher_id'],
                'homerooms' => ['class_section_id', 'teacher_id', 'session_year_id'], 'leadership' => ['user_id', 'session_year_id', 'kind', 'grade_level_id'],
            };
            if ($entry->exists) {
                abort_if((int) ($data['revision'] ?? 0) !== $entry->revision, 409, __('This allocation changed. Reload before saving.'));
                foreach ($identity as $key) {
                    abort_if((string) $entry->$key !== (string) ($row[$key] ?? null), 409, __('Append a new allocation to change the person or context.'));
                }
            }
            $overlaps = $class::query()->when($id !== null, fn ($q) => $q->whereKeyNot($id));
            foreach ($identity as $key) {
                $overlaps->where($key, $row[$key] ?? null);
            }
            if ($overlaps->whereDate('starts_on', '<=', $data['ends_on'])->whereDate('ends_on', '>=', $data['starts_on'])->exists()) {
                throw ValidationException::withMessages(['starts_on' => __('This person already has an overlapping assignment in this context.')]);
            }
            $before = $entry->exists ? $this->datedData($entry) : [];
            // Start dates and capture rules are history: an existing entry may be ended or corrected with an audit.
            $entry->fill($row);
            if ($entry->isDirty() || ! $entry->exists) {
                if ($entry->exists) {
                    $entry->revision++;
                }
                $entry->save();
                Audit::record('academics.'.$kind.'_saved', $entry, $before, $this->datedData($entry));
                Outbox::record(new AcademicAssignmentChanged(app(CurrentSchool::class)->id(), $kind, (int) $entry->id, $groupId));
                app(AcademicProjections::class)->refresh();
            }

            return $entry;
        }, 3);
    }

    public function room(int $id, ?int $gradeId = null): ClassSection
    {
        $room = ClassSection::query()->with('class')->findOrFail($id);
        if ($gradeId !== null && (int) $room->class->grade_level_id !== $gradeId) {
            throw ValidationException::withMessages(['class_section_id' => __('Map this homeroom to the selected grade first.')]);
        }

        return $room;
    }

    private function staffMember(int $id): void
    {
        User::query()->where('status', 1)->findOrFail($id);
        $member = SchoolMembership::query()->active()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $id)->whereNotIn('role', ['Student', 'Parent'])->exists();
        abort_unless($member, 404);
    }

    private function teacher(int $id): void
    {
        $teacher = Teacher::query()->findOrFail($id);
        $this->staffMember((int) $teacher->user_id);
    }

    private function learner(TeachingGroup $group, int $id, string $start, string $end): void
    {
        Students::query()->lockForUpdate()->findOrFail($id);
        $entries = Enrolment::query()->where('learner_id', $id)->where('session_year_id', $group->session_year_id)
            ->whereDate('starts_on', '<=', $end)->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $start))->orderBy('starts_on')->get();
        $cursor = $start;
        foreach ($entries as $entry) {
            if ($entry->starts_on->toDateString() > $cursor) {
                break;
            }
            $this->room((int) $entry->class_section_id, (int) $group->grade_level_id);
            if ($group->class_section_id !== null && (int) $entry->class_section_id !== (int) $group->class_section_id) {
                throw ValidationException::withMessages(['learner_id' => __('The learner is not enrolled in this group homeroom for the interval.')]);
            }
            $last = $entry->ends_on?->toDateString() ?? $end;
            if ($last >= $end) {
                return;
            }
            $cursor = CarbonImmutable::parse($last)->addDay()->toDateString();
        }
        throw ValidationException::withMessages(['learner_id' => __('The learner needs a matching dated grade enrolment covering the group membership.')]);
    }

    public function groupData(TeachingGroup $group): array
    {
        return ['id' => (int) $group->id, 'name' => $group->name, 'subject_id' => (int) $group->subject_id,
            'grade_level_id' => (int) $group->grade_level_id, 'session_year_id' => (int) $group->session_year_id,
            'class_section_id' => $group->class_section_id === null ? null : (int) $group->class_section_id,
            'active' => (bool) $group->active, 'revision' => (int) $group->revision];
    }

    public function datedData(Model $entry): array
    {
        $data = array_intersect_key($entry->toArray(), array_flip(['id', 'teaching_group_id', 'learner_id', 'teacher_id', 'class_section_id', 'session_year_id', 'user_id', 'grade_level_id', 'kind', 'reason', 'mark_rule', 'revision']));
        foreach ($data as $key => &$value) {
            if ($key === 'id' || str_ends_with($key, '_id') || $key === 'revision') {
                $value = $value === null ? null : (int) $value;
            }
        }
        unset($value);

        return $data + ['starts_on' => $entry->starts_on->toDateString(), 'ends_on' => $entry->ends_on->toDateString()];
    }
}
