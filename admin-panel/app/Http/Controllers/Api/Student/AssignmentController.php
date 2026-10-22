<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSessionConfig;
use App\Models\File;
use App\Models\Notification;
use App\Models\Semester;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\UserNotification;
use App\Services\Platform\Files\FileNames;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Student app: assignments and the student's submissions.
 */
class AssignmentController extends Controller
{
    use StudentAccess;

    /**
     * @param
     * assignment_id : 1    //OPTIONAL
     * subject_id : 1       //OPTIONAL
     */
    public function getAssignments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'assignment_id' => 'nullable|numeric',
            'subject_id' => 'nullable|numeric',
            'is_submitted' => 'nullable|numeric',
            'session_year_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $student = $request->user()->student;
            $student_subject = $student->subjects();
            $session_year = getSettings('session_year');
            $session_year_id = $request->session_year_id ?? $session_year['session_year'];
            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');
            $elective_subjects = $student_subject['elective_subject'] ?? [];
            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }

            $subject_id = array_merge($core_subjects, $elective_subjects);
            $student_session = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->first();
            $student_class_section_id = $student_session->class_section_id;
            $class_id = $student_session->class_section->class_id;

            $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);

            $currentSemester = null;
            if ($isClassHaveSemester) {
                $currentSemester = Semester::where('session_year_id', $session_year_id)
                    ->get()
                    ->first(fn ($s) => $s->current);
            }

            $data = Assignment::where('class_section_id', $student_class_section_id)->where('session_year_id', $session_year_id)
                ->whereIn('subject_id', $subject_id)->with(['file', 'subject', 'submission' => function ($q) use ($student) {
                    $q->where('student_id', $student->id)->with('file');
                }])->when($currentSemester, function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                });

            if ($request->assignment_id) {
                $data->where('id', $request->assignment_id);
            }
            if ($request->subject_id) {
                $data->where('subject_id', $request->subject_id);
            }
            if (isset($request->is_submitted)) {
                if ($request->is_submitted == 1) {
                    $data->whereHas('submission', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    });
                } elseif ($request->is_submitted == 0) {
                    $data->whereDoesntHave('submission', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    });
                }
            }
            $data = $data->orderBy('id', 'desc')->paginate();
            $data->getCollection()->each(function ($assignment) {
                $assignment->setRelation('submission', $assignment->submission->first());
            });

            return ResponseService::successResponse('Assignments Fetched Successfully', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * @param
     * assignment_id : 1    //OPTIONAL
     * subject_id : 1       //OPTIONAL
     */
    public function submitAssignment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|numeric',
            'subject_id' => 'nullable|numeric',
            'text_submission' => 'required_without:files',
            'files' => 'required_without:text_submission|array',

        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {

            $student = $request->user()->student;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $student_class_section_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->first()->class_section_id;
            // Only an assignment the student can see: their class section, this
            // session, and one of their subjects (as getAssignments lists them).
            $assignment = Assignment::where('id', $request->assignment_id)
                ->where('class_section_id', $student_class_section_id)
                ->where('session_year_id', $session_year_id)
                ->whereIn('subject_id', $this->studentSubjectIds($student))
                ->first();
            if (! $assignment) {
                return ResponseService::errorResponse('Invalid assignment', null, 103);
            }

            $assignment_submission = AssignmentSubmission::where('assignment_id', $request->assignment_id)->where('student_id', $student->id)->first();
            if (empty($assignment_submission)) {
                $assignment_submission = new AssignmentSubmission;
                $assignment_submission->assignment_id = $request->assignment_id;
                $assignment_submission->student_id = $student->id;
                $assignment_submission->session_year_id = $session_year_id;
                $assignment_submission->text_submission = $request->text_submission;
            } elseif ($assignment_submission->status == 2 && $assignment->resubmission) {
                // if assignment submission is rejected and
                // Assignment has resubmission allowed then change the status to resubmitted
                $assignment_submission->status = 3;
                $assignment_submission->text_submission = $request->text_submission;
                if ($assignment_submission->file) {
                    foreach ($assignment_submission->file as $file) {
                        if (Storage::disk('public')->exists($file->file_url)) {
                            Storage::disk('public')->delete($file->file_url);
                        }
                    }
                }
                $assignment_submission->file()->delete();
            } else {
                return ResponseService::errorResponse('You already have submitted your assignment.', null, 104);
            }

            $student_class_section_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->first()->class_section_id;
            $subject_teacher_id = SubjectTeacher::where(
                'class_section_id',
                $student_class_section_id
            )->where('subject_id', $assignment->subject_id)->pluck('teacher_id');
            $user = Teacher::whereIn('id', $subject_teacher_id)->pluck('user_id');
            $title = 'New submission';
            $body = $student->user->first_name.' '.$student->user->last_name.' submitted their assignment.';
            $type = 'assignment_submission';
            $image = null;
            $userinfo = null;

            $notification = new Notification;
            $notification->send_to = 2;
            $notification->session_year_id = $session_year_id;
            $notification->title = $title;
            $notification->message = $body;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();

            foreach ($user as $data) {
                $user_notification = new UserNotification;
                $user_notification->notification_id = $notification->id;
                $user_notification->user_id = $data;
                $user_notification->save();
            }
            $assignment_submission->save();
            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $key => $image) {
                    $file = new File;
                    $file->file_name = FileNames::forUpload($image);
                    $file->modal()->associate($assignment_submission);
                    $file->type = 1;
                    $file->file_url = $image->store('assignment', 'public');
                    $file->save();
                }
            }

            $submitted_assignment = AssignmentSubmission::where('id', $assignment_submission->id)->with('file')->get();

            return ResponseService::successResponse('Assignments Submitted Successfully', $submitted_assignment);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * @param
     * assignment_id : 1    //OPTIONAL
     * subject_id : 1       //OPTIONAL
     */
    public function deleteAssignmentSubmission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'assignment_submission_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $student = $request->user()->student;
            $assignment_submission = AssignmentSubmission::where('id', $request->assignment_submission_id)->where('student_id', $student->id)->with('file')->first();

            if (! empty($assignment_submission) && $assignment_submission->status == 0) {
                foreach ($assignment_submission->file as $file) {
                    if (Storage::disk('public')->exists($file->file_url)) {
                        Storage::disk('public')->delete($file->file_url);
                    }
                }
                $assignment_submission->file()->delete();
                $assignment_submission->delete();

                return ResponseService::successResponse('Assignments Deleted Successfully');
            } else {
                return ResponseService::errorResponse('You can not delete assignment', null, 110);
            }
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
