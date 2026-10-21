<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student\Concerns;

use App\Models\Students;

/**
 * What the signed-in student is enrolled in: the subjects they study, including
 * the electives they chose. Shared by the student app's API controllers.
 */
trait StudentAccess
{
    /**
     * Ids of the student's subjects this session: core plus chosen electives.
     *
     * @return list<int>
     */
    private function studentSubjectIds(Students $student): array
    {
        $student_subject = $student->subjects();
        $core_subjects = array_column($student_subject['core_subject'], 'subject_id');
        $elective_subjects = $student_subject['elective_subject'] ?? [];
        if ($elective_subjects) {
            $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
        }

        return array_values(array_map('intval', array_merge($core_subjects, $elective_subjects ?: [])));
    }
}
