<?php

namespace App\Actions\Transfers;

use App\Models\Timetable;
use App\Services\Platform\Tenancy\SchoolTable;

class TransferClassTimetable
{
    /**
     * Copies the timetable into the new year, pointing each lesson at the new
     * year's term and subject teacher. Run it after the subject teachers are
     * carried: a lesson whose subject teacher the new year does not have is
     * left out rather than linked to the old year's assignment.
     */
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        $semesters = YearIdMap::semesters($fromSessionId, $toSessionId);
        $subjectTeachers = YearIdMap::subjectTeachers($fromSessionId, $toSessionId, $semesters);

        Timetable::where('session_year_id', $fromSessionId)
            ->chunkById(500, function ($rows) use ($toSessionId, $semesters, $subjectTeachers) {
                $data = $rows
                    ->filter(fn ($row) => $row->subject_teacher_id === null || isset($subjectTeachers[$row->subject_teacher_id]))
                    ->map(function ($row) use ($toSessionId, $semesters, $subjectTeachers) {
                        return array_merge(
                            collect($row->getAttributes())
                                ->except(['id', 'created_at', 'updated_at'])
                                ->toArray(),
                            [
                                'session_year_id' => $toSessionId,
                                'semester_id' => YearIdMap::semester($row->semester_id, $semesters),
                                'subject_teacher_id' => $row->subject_teacher_id === null ? null : $subjectTeachers[$row->subject_teacher_id],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    })->values()->toArray();

                if (! empty($data)) {
                    SchoolTable::query('timetables')->insert($data);
                }
            });
    }
}
