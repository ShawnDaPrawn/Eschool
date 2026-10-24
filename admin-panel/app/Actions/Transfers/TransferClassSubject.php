<?php

namespace App\Actions\Transfers;

use App\Models\ClassSubject;
use App\Models\ElectiveSubjectGroup;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\DB;

class TransferClassSubject
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        DB::transaction(function () use ($fromSessionId, $toSessionId) {

            $groupIdMap = [];
            // Terms belong to a year: point the copies at the new year's terms.
            $terms = YearIdMap::terms($fromSessionId, $toSessionId);

            // Transfer Elective Subject Groups
            ElectiveSubjectGroup::where('session_year_id', $fromSessionId)
                ->chunkById(500, function ($rows) use ($toSessionId, $terms, &$groupIdMap) {

                    foreach ($rows as $row) {

                        $newGroup = ElectiveSubjectGroup::create([
                            'session_year_id' => $toSessionId,
                            'total_subjects' => $row->total_subjects,
                            'total_selectable_subjects' => $row->total_selectable_subjects,
                            'class_id' => $row->class_id,
                            'term_id' => YearIdMap::term($row->term_id, $terms),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $groupIdMap[$row->id] = $newGroup->id;
                    }
                });

            // Transfer Class Subjects
            ClassSubject::where('session_year_id', $fromSessionId)
                ->chunkById(500, function ($rows) use ($toSessionId, $terms, &$groupIdMap) {

                    $insertData = [];

                    foreach ($rows as $row) {

                        $newElectiveGroupId = null;

                        if (
                            $row->type === 'Elective' &&
                            $row->elective_subject_group_id &&
                            isset($groupIdMap[$row->elective_subject_group_id])
                        ) {
                            $newElectiveGroupId = $groupIdMap[$row->elective_subject_group_id];
                        }

                        $insertData[] = [
                            'session_year_id' => $toSessionId,
                            'class_id' => $row->class_id,
                            'term_id' => YearIdMap::term($row->term_id, $terms),
                            'type' => $row->type,
                            'subject_id' => $row->subject_id,
                            'elective_subject_group_id' => $newElectiveGroupId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (! empty($insertData)) {
                        SchoolTable::query('class_subjects')->insert($insertData);
                    }
                });
        });
    }
}
