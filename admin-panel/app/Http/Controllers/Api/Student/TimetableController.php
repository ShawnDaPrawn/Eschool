<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\TimetableCollection;
use App\Models\ClassSessionConfig;
use App\Models\Semester;
use App\Models\StudentSessions;
use App\Models\Timetable;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Throwable;

/**
 * Student app: the class timetable.
 */
class TimetableController extends Controller
{
    use StudentAccess;

    public function getTimetable(Request $request)
    {
        try {
            $student = $request->user()->student;
            $session_year_id = getSettings('session_year')['session_year'];

            $studentSession = StudentSessions::where('student_id', $student->id)
                ->where('session_year_id', $session_year_id)
                ->first();

            $class_section_id = $studentSession->class_section_id;
            $class_id = $studentSession->class_section->class_id; // correct way to get class_id

            $student_subject = $student->subjects();
            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');
            $elective_subjects = $student_subject['elective_subject'] ?? [];

            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }

            $subject_ids = array_merge($core_subjects, $elective_subjects);

            $currentSemester = Semester::where('session_year_id', $session_year_id)
                ->get()
                ->first(fn ($s) => $s->current);

            $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);

            $timetables = Timetable::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->when(
                    $isClassHaveSemester && $currentSemester,
                    fn ($q) => $q->where('semester_id', $currentSemester->id)
                )
                ->with(['subject_teacher' => fn ($q) => $q->whereIn('subject_id', $subject_ids)])
                ->orderBy('day', 'asc')
                ->orderBy('start_time', 'asc')
                ->get()
                ->filter(fn ($timetable) => $timetable->subject_teacher !== null);

            return ResponseService::successResponse('Timetable Fetched Successfully', new TimetableCollection($timetables));
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
