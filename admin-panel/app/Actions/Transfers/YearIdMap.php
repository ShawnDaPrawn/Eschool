<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Models\SubjectTeacher;
use App\Models\Term;

/**
 * Old-to-new ids for rows that belong to a session year, so a transfer never
 * copies an id that points back into the previous year. Terms (terms)
 * match by name, then by their order in the year; subject teachers match by
 * homeroom, subject, teacher and the mapped term.
 */
class YearIdMap
{
    /** @return array<int, int> old term id => new term id */
    public static function terms(int $fromSessionId, int $toSessionId): array
    {
        $from = Term::where('session_year_id', $fromSessionId)->orderBy('start_date')->orderBy('id')->get(['id', 'name', 'grade_from', 'grade_to'])->values();
        $to = Term::where('session_year_id', $toSessionId)->orderBy('start_date')->orderBy('id')->get(['id', 'name', 'grade_from', 'grade_to'])->values();

        $key = fn ($term) => self::key($term->name).'|'.$term->grade_from.'|'.$term->grade_to;
        $targets = $to->groupBy($key);
        $sources = $from->groupBy($key);
        $positions = [];
        $map = [];
        foreach ($from as $term) {
            $band = $term->grade_from.'|'.$term->grade_to;
            $position = $positions[$band] ?? 0;
            $positions[$band] = $position + 1;
            $named = $targets->get($key($term));
            $match = $named?->count() === 1 && $sources->get($key($term))->count() === 1
                ? $named->first()
                : $to->where('grade_from', $term->grade_from)->where('grade_to', $term->grade_to)->values()->get($position);
            if ($match !== null) {
                $map[$term->id] = $match->id;
            }
        }

        return $map;
    }

    /**
     * The new year's id for a term of the old year. A term the new year has no
     * match for must be reviewed; never widen its allocation to the whole year.
     *
     * @param  array<int, int>  $terms
     */
    public static function term(?int $oldId, array $terms): ?int
    {
        if ($oldId === null) {
            return null;
        }
        if (! array_key_exists($oldId, $terms)) {
            throw new \DomainException(__('Add a matching term for the grade range before carrying academic records forward.'));
        }

        return $terms[$oldId];
    }

    /**
     * @param  array<int, int>  $terms
     * @return array<int, int> old subject teacher id => new subject teacher id
     */
    public static function subjectTeachers(int $fromSessionId, int $toSessionId, array $terms): array
    {
        $target = [];
        foreach (SubjectTeacher::where('session_year_id', $toSessionId)->get(['id', 'class_section_id', 'subject_id', 'teacher_id', 'term_id']) as $row) {
            $target[self::assignment($row->class_section_id, $row->subject_id, $row->teacher_id, $row->term_id)] ??= $row->id;
        }

        $map = [];
        foreach (SubjectTeacher::where('session_year_id', $fromSessionId)->get(['id', 'class_section_id', 'subject_id', 'teacher_id', 'term_id']) as $row) {
            $key = self::assignment($row->class_section_id, $row->subject_id, $row->teacher_id, self::term($row->term_id, $terms));
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

    private static function assignment(mixed $homeroom, mixed $subject, mixed $teacher, mixed $term): string
    {
        return implode('|', [(int) $homeroom, (int) $subject, (int) $teacher, $term === null ? '' : (int) $term]);
    }
}
