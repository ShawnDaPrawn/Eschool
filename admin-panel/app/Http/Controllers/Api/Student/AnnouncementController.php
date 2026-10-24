<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassSessionConfig;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Models\Term;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Student app: announcements for the student's class and subjects.
 */
class AnnouncementController extends Controller
{
    use StudentAccess;

    public function getAnnouncements(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:subject,noticeboard,class',
            'subject_id' => 'required_if:type,subject|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $request->user()->student;
            $class_id = $student->class_section->class->id;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $table = null;
            $student_class_section_id = StudentSessions::where('student_id', $student->id)
                ->where('session_year_id', $session_year_id)->first()->class_section_id;

            if (isset($request->type) && $request->type == 'subject') {
                $table = SubjectTeacher::where('class_section_id', $student_class_section_id)
                    ->where('subject_id', $request->subject_id)->get()->pluck('id');
                if (empty($table)) {
                    return ResponseService::errorResponse('Invalid Subject ID', null, 106);
                }
            }

            $data = Announcement::with('file')->where('session_year_id', $session_year_id)->latest();

            if (! isset($request->type)) {
                // Everything this student may see: the noticeboard, their class and
                // class section, and their own subjects in their class section.
                $subject_teacher_ids = SubjectTeacher::where('class_section_id', $student_class_section_id)
                    ->whereIn('subject_id', $this->studentSubjectIds($student))
                    ->pluck('id');
                $data = $data->where(function ($query) use ($class_id, $student_class_section_id, $subject_teacher_ids) {
                    $query->where('table_type', '')
                        ->orWhere(fn ($q) => $q->where('table_type', "App\Models\ClassSchool")->where('table_id', $class_id))
                        ->orWhere(fn ($q) => $q->where('table_type', "App\Models\ClassSection")->where('table_id', $student_class_section_id))
                        ->orWhere(fn ($q) => $q->where('table_type', "App\Models\SubjectTeacher")->whereIn('table_id', $subject_teacher_ids));
                });
            }

            if (isset($request->type) && $request->type == 'noticeboard') {
                // The app's notice board: school-wide notices plus those for the
                // student's class and class section (the app has no other list for them).
                $data = $data->where(function ($query) use ($class_id, $student_class_section_id) {
                    $query->where('table_type', '')
                        ->orWhere(fn ($q) => $q->where('table_type', "App\Models\ClassSchool")->where('table_id', $class_id))
                        ->orWhere(fn ($q) => $q->where('table_type', "App\Models\ClassSection")->where('table_id', $student_class_section_id));
                });
            }

            if (isset($request->type) && $request->type == 'class') {
                $data = $data->where('table_type', "App\Models\ClassSchool")->where('table_id', $class_id);
            }

            if (isset($request->type) && $request->type == 'subject') {
                $isClassHaveTerm = ClassSessionConfig::classHasTerm($class_id, $session_year_id);
                $data = $data->where('table_type', "App\Models\SubjectTeacher")->whereIn('table_id', $table);
                if ($isClassHaveTerm) {
                    $currentTerm = Term::currentForClass((int) $session_year_id, (int) $class_id);

                    $data->where('term_id', $currentTerm?->id);
                }
            }

            $data = $data->orderBy('id', 'desc')->paginate();

            return ResponseService::successResponse('Announcement Details Fetched Successfully', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
