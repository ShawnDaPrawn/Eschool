<?php

namespace App\Actions\Transfers;

use App\Models\ClassTeacher;
use App\Models\SubjectTeacher;
use App\Services\Platform\Tenancy\SchoolTable;

class TransferClassTeacherAndSubjectTeacher
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        // Terms belong to a year: point the copies at the new year's terms.
        $terms = YearIdMap::terms($fromSessionId, $toSessionId);

        ClassTeacher::where('session_year_id', $fromSessionId)->whereNull('homeroom_allocation_id')
            ->chunkById(500, function ($rows) use ($toSessionId, $terms) {

                $insertClassTeacherData = $rows->map(function (ClassTeacher $row) use ($toSessionId, $terms) {
                    return array_merge(
                        collect($row->getAttributes())
                            ->except(['id', 'created_at', 'updated_at'])
                            ->toArray(),
                        [
                            'session_year_id' => $toSessionId,
                            'term_id' => YearIdMap::term($row->term_id, $terms),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                })->toArray();
                if (! empty($insertClassTeacherData)) {
                    SchoolTable::query('class_teachers')->insert($insertClassTeacherData);
                }
            });

        SubjectTeacher::where('session_year_id', $fromSessionId)->whereNull('teaching_allocation_id')
            ->chunkById(500, function ($rows) use ($toSessionId, $terms) {

                $insertSubjectTeacherData = $rows->map(function (SubjectTeacher $row) use ($toSessionId, $terms) {
                    return array_merge(
                        collect($row->getAttributes())
                            ->except(['id', 'created_at', 'updated_at'])
                            ->toArray(),
                        [
                            'session_year_id' => $toSessionId,
                            'term_id' => YearIdMap::term($row->term_id, $terms),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                })->toArray();

                if (! empty($insertSubjectTeacherData)) {
                    SchoolTable::query('subject_teachers')->insert($insertSubjectTeacherData);
                }
            });
    }
}
