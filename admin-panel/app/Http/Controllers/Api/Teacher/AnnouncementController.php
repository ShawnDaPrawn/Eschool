<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\File;
use App\Models\Students;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Services\Platform\Files\FileNames;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Teacher app: subject announcements.
 */
class AnnouncementController extends Controller
{
    use TeacherAccess;

    public function getAnnouncement(Request $request): JsonResponse
    {
        ResponseService::noPermissionThenSendJson('announcement-list');

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
            $teacher = Auth::user()->teacher;

            $subjectTeacherQuery = SubjectTeacher::where('teacher_id', $teacher->id);

            if ($request->filled('class_section_id')) {
                $subjectTeacherQuery->where('class_section_id', $request->class_section_id);
                $this->applySemesterFilter($subjectTeacherQuery, $request->class_section_id, $sessionYearId, $request->semester_id);
            }

            if ($request->filled('subject_id')) {
                $subjectTeacherQuery->where('subject_id', $request->subject_id);
            }

            $subjectTeacherIds = $subjectTeacherQuery->pluck('id');

            $data = Announcement::with('table.subject', 'file')
                ->where('table_type', 'App\Models\SubjectTeacher')
                ->whereIn('table_id', $subjectTeacherIds)
                ->orderByDesc('id')
                ->paginate();

            return ResponseService::successResponse('Announcement Fetched Successfully.', $data);
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function sendAnnouncement(Request $request)
    {
        ResponseService::noPermissionThenSendJson('announcement-create');

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|numeric|exists:class_sections,id',
            'subject_id' => 'required|numeric|exists:subjects,id',
            'semester_id' => 'nullable|numeric|exists:semesters,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable',
            'file.*' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif',
        ], [
            'class_section_id.exists' => 'Selected class section does not exist.',
            'subject_id.exists' => 'Selected subject does not exist.',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $session_year_id = getSettings('session_year')['session_year'];
            $teacher_id = Auth::user()->teacher->id;

            // Semester (conditional)
            $classId = ClassSection::where('id', $request->class_section_id)->value('class_id');
            $isClassHaveSemester = ClassSessionConfig::classHasSemester($classId, $session_year_id);
            $semesterId = $isClassHaveSemester ? $request->semester_id : null;

            // Verify teacher is assigned (semester-aware)
            $subject_teacher = SubjectTeacher::where('teacher_id', $teacher_id)
                ->where('class_section_id', $request->class_section_id)
                ->where('subject_id', $request->subject_id)
                ->when($isClassHaveSemester && $semesterId, fn ($q) => $q->where(
                    fn ($q) => $q->where('semester_id', $semesterId)->orWhereNull('semester_id')
                ))
                ->with('subject')
                ->first();

            if (! $subject_teacher) {
                return ResponseService::errorResponse('You are not assigned to this class section and subject combination.', null, 403);
            }

            $announcement = new Announcement;
            $announcement->title = trim($request->title);
            $announcement->description = trim($request->description);
            $announcement->session_year_id = $session_year_id;
            $announcement->semester_id = $semesterId;
            $announcement->table()->associate($subject_teacher);

            // Get students in the class section
            $user = Students::select('user_id')
                ->where('class_section_id', $request->class_section_id)
                ->pluck('user_id')
                ->filter()
                ->toArray();

            $announcement->save();

            if (! empty($user)) {
                $title = 'New announcement in '.$subject_teacher->subject->name;
                $body = $request->title;
                sendSimpleNotification($user, $title, $body, 'class_section', null, null);
            }

            // File uploads
            if ($request->hasFile('file')) {
                foreach (Arr::wrap($request->file('file')) as $file_upload) {
                    $file = new File;
                    $file->file_name = FileNames::forUpload($file_upload);
                    $file->type = 1;
                    $file->file_url = $file_upload->store('announcement', 'public');
                    $file->modal()->associate($announcement);
                    $file->save();
                }
            }

            return ResponseService::successResponse('data_store_successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateAnnouncement(Request $request)
    {
        ResponseService::noPermissionThenSendJson('announcement-edit');

        $validator = Validator::make($request->all(), [
            'announcement_id' => 'required|numeric',
            'class_section_id' => 'required|numeric',
            'subject_id' => 'required|numeric',
            'title' => 'required',
            'file' => 'nullable',
            'file.*' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $teacher_id = $this->currentTeacher()->id;
            $announcement = Announcement::findOrFail($request->announcement_id);
            if (! $this->ownsAnnouncement($announcement)) {
                return ResponseService::errorResponse('no_permission_message', null, 403);
            }
            $announcement->title = $request->title;
            $announcement->description = $request->description;

            $subject_teacher = SubjectTeacher::where(['teacher_id' => $teacher_id, 'class_section_id' => $request->class_section_id, 'subject_id' => $request->subject_id])->with('subject')->firstOrFail();
            $announcement->table()->associate($subject_teacher);
            $user = Students::select('user_id')->where('class_section_id', $request->class_section_id)->get()->pluck('user_id');

            $title = 'Update announcement in '.$subject_teacher->subject->name;
            $body = $request->title;
            $image = null;
            $userinfo = null;

            $announcement->save();
            sendSimpleNotification($user, $title, $body, 'class_section', $image, $userinfo);
            if ($request->hasFile('file')) {
                foreach (Arr::wrap($request->file('file')) as $file_upload) {
                    $file = new File;
                    $file->file_name = FileNames::forUpload($file_upload);
                    $file->type = 1;
                    $file->file_url = $file_upload->store('announcement', 'public');
                    $file->modal()->associate($announcement);
                    $file->save();
                }
            }

            return ResponseService::successResponse('data_update_successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteAnnouncement(Request $request)
    {
        ResponseService::noPermissionThenSendJson('announcement-delete');

        $validator = Validator::make($request->all(), [
            'announcement_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $announcement = Announcement::findorFail($request->announcement_id);
            if (! $this->ownsAnnouncement($announcement)) {
                return ResponseService::errorResponse('no_permission_message', null, 403);
            }
            $announcement->delete();

            return ResponseService::successResponse('data_delete_successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
