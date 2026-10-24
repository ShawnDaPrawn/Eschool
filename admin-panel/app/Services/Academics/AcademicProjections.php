<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\TeachingAllocation;
use App\Models\ClassSection;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Teacher;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\DB;

/** Stable v1 IDs from canonical dated assignments; no model deletion cascades. */
class AcademicProjections
{
    public function refresh(): void
    {
        DB::transaction(function () {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $today = app(AcademicAuthority::class)->today();
            $subjectIds = $classIds = [];
            $eligible = Teacher::query()->whereHas('user', fn ($q) => $q->where('status', 1))
                ->whereIn('user_id', SchoolMembership::query()->active()->where('school_id', app(CurrentSchool::class)->id())->whereNotIn('role', ['Parent', 'Student'])->select('user_id'))
                ->pluck('id')->mapWithKeys(fn ($id) => [(int) $id => true])->all();
            $allocations = TeachingAllocation::query()->with('group')->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->get();
            foreach ($allocations as $allocation) {
                $group = $allocation->group;
                if ($group === null || ! $group->active || ! isset($eligible[(int) $allocation->teacher_id])) {
                    continue;
                }
                // A mixed group cannot grant whole-class v1 authority for a single member.
                // Its learners/teachers use the exact group APIs instead.
                $rooms = $group->class_section_id === null ? [] : [$group->class_section_id];
                $rooms = ClassSection::query()->whereIn('id', $rooms)
                    ->whereHas('class', fn ($q) => $q->where('grade_level_id', $group->grade_level_id))->pluck('id')->all();
                foreach ($rooms as $room) {
                    $subjectIds[] = $this->put('subject_teachers', ['teaching_allocation_id' => $allocation->id, 'class_section_id' => $room],
                        ['subject_id' => $group->subject_id, 'teacher_id' => $allocation->teacher_id, 'session_year_id' => $group->session_year_id, 'term_id' => null]);
                }
            }
            foreach (HomeroomAllocation::query()->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->get() as $allocation) {
                if (! isset($eligible[(int) $allocation->teacher_id])) {
                    continue;
                }
                $classIds[] = $this->put('class_teachers', ['homeroom_allocation_id' => $allocation->id],
                    ['class_teacher_id' => $allocation->teacher_id, 'class_section_id' => $allocation->class_section_id, 'session_year_id' => $allocation->session_year_id, 'term_id' => null]);
            }
            foreach ([['subject_teachers', 'teaching_allocation_id', $subjectIds], ['class_teachers', 'homeroom_allocation_id', $classIds]] as [$table, $key, $ids]) {
                SchoolTable::query($table)->whereNotNull($key)->whereNotIn('id', $ids)->whereNull('deleted_at')->update(['deleted_at' => now(), 'updated_at' => now()]);
            }
        }, 3);
    }

    private function put(string $table, array $keys, array $values): int
    {
        $source = $table === 'subject_teachers' ? 'teaching_allocation_id' : 'homeroom_allocation_id';
        $logical = array_diff_key($keys + $values, [$source => true]);
        $query = SchoolTable::query($table);
        foreach ($logical as $key => $value) {
            $query->where($key, $value);
        }
        // Adopt an exact existing logical assignment, preserving its v1 ID.
        // Multiple active groups flatten to one row for the same teacher/context.
        $id = (clone $query)->orderBy('id')->value('id');
        $write = $keys + $values + ['deleted_at' => null, 'updated_at' => now()];
        if ($id === null) {
            return SchoolTable::query($table)->insertGetId($write + ['created_at' => now()]);
        }
        SchoolTable::query($table)->where('id', $id)->update($write);

        return (int) $id;
    }
}
