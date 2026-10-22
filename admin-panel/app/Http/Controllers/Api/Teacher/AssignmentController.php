<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassSubject;
use App\Models\File;
use App\Models\Notification;
use App\Models\Semester;
use App\Models\Students;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\UserNotification;
use App\Services\Platform\Files\FileNames;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Teacher app: assignments and reviewing students' submissions.
 */
class AssignmentController extends Controller
{
    use TeacherAccess;

    public function getAssignment(Request $request): JsonResponse
    {
        ResponseService::noPermissionThenSendJson('assignment-list');

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'nullable|numeric',
            'subject_id' => 'nullable|numeric',
            'semester_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $sessionYearId = getSettings('session_year')['session_year'];

            $query = Assignment::assignmentteachers()
                ->where('session_year_id', $sessionYearId)
                ->with('class_section', 'file', 'subject', 'semester');

            if ($request->filled('class_section_id')) {
                $query->where('class_section_id', $request->class_section_id);
                if ($request->semester_id) {
                    $this->applySemesterFilter($query, $request->class_section_id, $sessionYearId, $request->semester_id);
                }
            }

            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            $data = $query->orderByDesc('id')->paginate();

            $data->getCollection()->transform(function ($item) {
                $item->subject->semester = $item->semester;
                unset($item->semester);

                return $item;
            });

            return ResponseService::successResponse('Assignment Fetched Successfully.', $data);
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function createAssignment(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-create');

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|numeric|exists:class_sections,id',
            'subject_id' => 'required|numeric|exists:subjects,id',
            'semester_id' => 'nullable|numeric|exists:semesters,id',
            'name' => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'due_date' => 'required|date_format:d-m-Y H:i',
            'points' => 'nullable|numeric|min:0',
            'resubmission' => 'nullable|boolean',
            'extra_days_for_resubmission' => 'nullable|numeric|min:0',
        ], [
            'class_section_id.exists' => 'Selected class section does not exist.',
            'subject_id.exists' => 'Selected subject does not exist.',
            'due_date.date_format' => 'Due date must be in format DD-MM-YYYY HH:MM (e.g., 07-08-2025 15:45)',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $session_year_id = getSettings('session_year')['session_year'];

            // Semester
            $classId = ClassSection::where('id', $request->class_section_id)->value('class_id');
            $isClassHaveSemester = ClassSessionConfig::classHasSemester($classId, $session_year_id);
            $semesterId = $isClassHaveSemester ? $request->semester_id : null;

            if ($isClassHaveSemester && ! $semesterId) {
                return ResponseService::errorResponse('Semester id required.', null, 400);
            }

            if (! $this->teachesSubject($request->class_section_id, $request->subject_id, $semesterId)) {
                return ResponseService::errorResponse('You are not assigned to this class section and subject combination.', null, 403);
            }

            $due_date = Carbon::createFromFormat('d-m-Y H:i', $request->due_date);

            $assignment = new Assignment;
            $assignment->class_section_id = $request->class_section_id;
            $assignment->subject_id = $request->subject_id;
            $assignment->semester_id = $semesterId;
            $assignment->name = trim($request->name);
            $assignment->instructions = trim($request->instructions);
            $assignment->due_date = $due_date->format('Y-m-d H:i:s');
            $assignment->points = $request->points;
            $assignment->resubmission = $request->resubmission ? 1 : 0;
            $assignment->extra_days_for_resubmission = $request->resubmission ? $request->extra_days_for_resubmission : null;
            $assignment->session_year_id = $session_year_id;

            // This class's row for the subject: another class may take it as an elective.
            $class_subject = ClassSubject::where('subject_id', $request->subject_id)
                ->where('class_id', ClassSection::where('id', $request->class_section_id)->value('class_id'))
                ->where('session_year_id', $session_year_id)
                ->first();

            if (! $class_subject) {
                return ResponseService::errorResponse('Subject is not assigned to any class.', null, 400);
            }

            // Get students based on subject type
            $user = [];
            if ($class_subject->type == 'Elective') {
                $student_ids = Students::where('class_section_id', $request->class_section_id)->pluck('id');

                foreach ($student_ids as $student_id) {
                    $student_subject = StudentSubject::where('student_id', $student_id)
                        ->where('subject_id', $request->subject_id)
                        ->where('session_year_id', $session_year_id)
                        ->first();

                    if ($student_subject) {
                        $user_id = Students::where('id', $student_subject->student_id)
                            ->where('class_section_id', $request->class_section_id)
                            ->pluck('user_id')
                            ->first();
                        if ($user_id) {
                            $user[] = $user_id;
                        }
                    }
                }
            } else {
                $user = Students::where('class_section_id', $request->class_section_id)
                    ->pluck('user_id')
                    ->toArray();
            }

            // Notification
            $subject_name = Subject::where('id', $request->subject_id)->value('name');
            $title = 'New assignment added in '.$subject_name;
            $body = $request->name;
            $type = 'assignment';

            $notification = new Notification;
            $notification->send_to = 3;
            $notification->session_year_id = $session_year_id;
            $notification->title = $title;
            $notification->message = $body;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();

            foreach ($user as $user_id) {
                if ($user_id) {
                    $user_notification = new UserNotification;
                    $user_notification->notification_id = $notification->id;
                    $user_notification->user_id = $user_id;
                    $user_notification->save();
                }
            }

            $assignment->save();

            if (! empty($user)) {
                sendSimpleNotification($user, $title, $body, $type, null, null);
            }

            // File uploads
            if ($request->hasFile('file')) {
                foreach ($request->file as $file_upload) {
                    $file = new File;
                    $file->file_name = FileNames::forUpload($file_upload);
                    $file->type = 1;
                    $file->file_url = $file_upload->store('assignment', 'public');
                    $file->modal()->associate($assignment);
                    $file->save();
                }
            }

            return ResponseService::successResponse('Assignment Created Successfully.');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateAssignment(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-edit');

        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|numeric',
            'class_section_id' => 'required|numeric',
            'subject_id' => 'required|numeric',
            'name' => 'required',
            'instructions' => 'nullable',
            'due_date' => 'required|date',
            'points' => 'nullable',
            'resubmission' => 'nullable|boolean',
            'extra_days_for_resubmission' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $assignment = Assignment::assignmentTeachers()->findOrFail($request->assignment_id);

            if (! $this->teachesSubject($request->class_section_id, $request->subject_id)) {
                return ResponseService::errorResponse('You are not assigned to this class section and subject combination.', null, 403);
            }

            $assignment->class_section_id = $request->class_section_id;
            $assignment->subject_id = $request->subject_id;
            $assignment->name = $request->name;
            $assignment->instructions = $request->instructions;
            $assignment->due_date = Carbon::parse($request->due_date)->format('Y-m-d H:i:s');
            $assignment->points = $request->points;
            if ($request->resubmission) {
                $assignment->resubmission = 1;
                $assignment->extra_days_for_resubmission = $request->extra_days_for_resubmission;
            } else {
                $assignment->resubmission = 0;
                $assignment->extra_days_for_resubmission = null;
            }

            $assignment->session_year_id = $session_year_id;
            $subject_name = Subject::select('name')->where('id', $request->subject_id)->pluck('name')->first();
            $title = 'Update assignment in '.$subject_name;
            $body = $request->name;
            $type = 'assignment';
            $image = null;
            $userinfo = null;

            $user = Students::select('user_id')->where('class_section_id', $request->class_section_id)->get()->pluck('user_id');

            $notification = new Notification;
            $notification->send_to = 3;
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

            $assignment->save();
            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);

            if ($request->hasFile('file')) {
                foreach ($request->file as $file_upload) {
                    $file = new File;
                    $file->file_name = FileNames::forUpload($file_upload);
                    $file->type = 1;
                    $file->file_url = $file_upload->store('assignment', 'public');
                    $file->modal()->associate($assignment);
                    $file->save();
                }
            }

            return ResponseService::successResponse('data_store_successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteAssignment(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-delete');

        try {
            $assignment = Assignment::assignmentTeachers()->findOrFail($request->assignment_id);
            $assignment->delete();

            return ResponseService::successResponse('data_delete_successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getAssignmentSubmission(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-submission');
        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|nullable|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $sql = AssignmentSubmission::assignmentsubmissionteachers()->with(
                'assignment.subject:id,name',
                'student:id,user_id',
                'student.user:first_name,last_name,id,image',
                'file'
            );
            $data = $sql->where('assignment_id', $request->assignment_id)->get();

            return ResponseService::successResponse('Assignment Fetched Successfully.', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateAssignmentSubmission(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-submission');

        $validator = Validator::make($request->all(), [
            'assignment_submission_id' => 'required|numeric',
            'status' => 'required|numeric|in:1,2',
            'points' => 'nullable|numeric',
            'feedback' => 'nullable',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $assignment_submission = AssignmentSubmission::assignmentSubmissionTeachers()->findOrFail($request->assignment_submission_id);

            // The submission scope matches class sections and subjects separately; also
            // require the assignment itself to be one of this teacher's.
            if (! Assignment::assignmentTeachers()->whereKey($assignment_submission->assignment_id)->exists()) {
                return ResponseService::errorResponse('no_permission_message', null, 403);
            }

            $assignment_submission->feedback = $request->feedback;
            if ($request->status == 1) {
                $assignment_submission->points = $request->points;
            } else {
                $assignment_submission->points = null;
            }

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $assignment_submission->status = $request->status;
            $assignment_submission->save();

            $assignment_data = Assignment::where('id', $assignment_submission->assignment_id)->with('subject')->first();
            $user = Students::select('user_id')->where('id', $assignment_submission->student_id)->get()->pluck('user_id');
            $title = '';
            $body = '';
            if ($request->status == 2) {
                $title = 'Assignment rejected';
                $body = $assignment_data->name.' rejected in '.$assignment_data->subject->name.' subject';
            }
            if ($request->status == 1) {
                $title = 'Assignment accepted';
                $body = $assignment_data->name.' accepted in '.$assignment_data->subject->name.' subject';
            }
            $type = 'assignment';
            $image = null;
            $userinfo = null;

            $notification = new Notification;
            $notification->session_year_id = $session_year_id;
            $notification->send_to = 3;
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

            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);

            return ResponseService::successResponse('data_update_successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
