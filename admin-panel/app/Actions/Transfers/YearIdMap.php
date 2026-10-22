<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Models\Semester;
use App\Models\SubjectTeacher;

/**
 * Old-to-new ids for rows that belong to a session year, so a transfer never
 * copies an id that points back into the previous year. Terms (semesters)
 * match by name, then by their order in the year; subject teachers match by
 * homeroom, subject, teacher and the mapped term.
 */
class YearIdMap
{
    /** @return array<int, int> old semester id => new semester id */
    public static function semesters(int $fromSessionId, int $toSessionId): array
    {
        $from = Semester::where('session_year_id', $fromSessionId)->orderBy('start_date')->orderBy('id')->get(['id', 'name'])->values();
        $to = Semester::where('session_year_id', $toSessionId)->orderBy('start_date')->orderBy('id')->get(['id', 'name'])->values();

        $byName = [];
        foreach ($to as $semester) {
            $byName[self::key($semester->name)] ??= $semester->id;
        }

        $map = [];
        foreach ($from as $position => $semester) {
            $match = $byName[self::key($semester->name)] ?? $to->get($position)?->id;
            if ($match !== null) {
                $map[$semester->id] = $match;
            }
        }

        return $map;
    }

    /**
     * The new year's id for a term of the old year. A term the new year has no
     * match for becomes null (the whole year) rather than the old year's id.
     *
     * @param  array<int, int>  $semesters
     */
    public static function semester(?int $oldId, array $semesters): ?int
    {
        return $oldId === null ? null : ($semesters[$oldId] ?? null);
    }

    /**
     * @param  array<int, int>  $semesters
     * @return array<int, int> old subject teacher id => new subject teacher id
     */
    public static function subjectTeachers(int $fromSessionId, int $toSessionId, array $semesters): array
    {
        $target = [];
        foreach (SubjectTeacher::where('session_year_id', $toSessionId)->get(['id', 'class_section_id', 'subject_id', 'teacher_id', 'semester_id']) as $row) {
            $target[self::assignment($row->class_section_id, $row->subject_id, $row->teacher_id, $row->semester_id)] ??= $row->id;
        }

        $map = [];
        foreach (SubjectTeacher::where('session_year_id', $fromSessionId)->get(['id', 'class_section_id', 'subject_id', 'teacher_id', 'semester_id']) as $row) {
            $key = self::assignment($row->class_section_id, $row->subject_id, $row->teacher_id, self::semester($row->semester_id, $semesters));
            if (isset($target[$key])) {
                $map[$row->id] = $target[$key];
            }
        }

        return $map;
    }

    private static function key(?string $name): string
    {
        return mb_strtolower(trim((string) $name));
    }

    private static function assignment(mixed $homeroom, mixed $subject, mixed $teacher, mixed $semester): string
    {
        return implode('|', [(int) $homeroom, (int) $subject, (int) $teacher, $semester === null ? '' : (int) $semester]);
    }
}
