<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSessionConfig;
use App\Models\ClassTeacher;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\Term;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Parent app: a child's teachers.
 */
class TeacherController extends Controller
{
    use ParentAccess;

    public function getTeachers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $children = $this->resolveChild($request);
            $student_subject = $children->subjects();
            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');

            $elective_subjects = $student_subject['elective_subject'] ?? [];
            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }
            $subject_id = array_merge($core_subjects, $elective_subjects);

            // Resolve class_section_id from StudentSessions for the current session year
            $studentSession = StudentSessions::where('session_year_id', $session_year_id)
                ->where('student_id', $children->id)->first();
            $class_section_id = $studentSession ? $studentSession->class_section_id : $studentSession->class_section_id;

            $currentTerm = Term::currentForClass((int) $session_year_id, (int) $studentSession->class_section->class_id);

            $isClassHaveTerm = ClassSessionConfig::classHasTerm(
                $studentSession->class_section->class_id,
                $session_year_id
            );

            $class_teachers_id = ClassTeacher::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->when($isClassHaveTerm, function ($query) use ($currentTerm) {
                    $query->where('term_id', $currentTerm?->id);
                })
                ->pluck('class_teacher_id')->toArray();

            $subject_teacher_id = SubjectTeacher::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->whereIn('subject_id', $subject_id)
                ->when($isClassHaveTerm, function ($query) use ($currentTerm) {
                    $query->where('term_id', $currentTerm?->id);
                })
                ->pluck('teacher_id')->toArray();

            $teacher_ids = array_merge($class_teachers_id, $subject_teacher_id);

            // No dob: a teacher's date of birth is their default password.
            $teachers = Teacher::whereIn('id', $teacher_ids)->with([
                'user:id,first_name,last_name,image,mobile,email',
                'subjects' => function ($query) use ($class_section_id, $session_year_id) {
                    $query->where('class_section_id', $class_section_id)
                        ->where('session_year_id', $session_year_id)
                        ->with('subject');
                },
            ])->get();
            foreach ($teachers as $teacher) {
                $subjectData = [];
                foreach ($teacher->subjects as $subject) {
                    $subjectData[] = $subject->subject->name;
                }
                $subjects = implode(', ', $subjectData);

                $data[] = [
                    'id' => $teacher->id,
                    'user_id' => $teacher->user->id,
                    'first_name' => $teacher->user->first_name,
                    'last_name' => $teacher->user->last_name,
                    'qualification' => $teacher->qualification,
                    'email' => $teacher->user->email,
                    'image' => $teacher->user->image,
                    'mobile_no' => $teacher->user->mobile,
                    'subjects' => $subjects,
                ];
            }

            return ResponseService::successResponse('Teacher Details Fetched Successfully', $data ?? [], [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
