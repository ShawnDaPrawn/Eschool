<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassTeacher;
use App\Models\File;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\LeaveMaster;
use App\Models\Notification;
use App\Models\Parents;
use App\Models\Semester;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\Teacher;
use App\Models\UserNotification;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher app: the teacher's own leave, and deciding their students' leave.
 */
class LeaveController extends Controller
{
    use TeacherAccess;

    public function applyLeave(Request $request)
    {
        $allowed = ['reason', 'leave_details', 'files'];

        // reject any extra keys
        $unknown = array_diff(array_keys($request->all()), $allowed);

        if (! empty($unknown)) {
            ResponseService::validationError('Unknown fields: '.implode(', ', $unknown));
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required',
            'leave_details.*' => 'required|array',

            // This enforces that "files" must be an array
            'files' => 'nullable|array',

            // Only if files is a proper array, validate each file
            'files.*' => 'nullable|file|mimetypes:image/jpeg,image/png,image/webp,image/gif,image/bmp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            // $sessionYear = SessionYear::where('id', $session_year_id)->first();

            $leave_master = LeaveMaster::where('session_year_id', $session_year_id)->first();

            // $public_holiday = Holiday::whereDate('date', '>=', $sessionYear->start_date)->whereDate('date', '<=', $sessionYear->end_date)->get()->pluck('date')->toArray();

            if (! $leave_master) {
                ResponseService::errorResponse('Kindly contact the school admin to update settings for continued access.');
            }

            $dates = array_column($request->leave_details, 'date');
            $from_date = min($dates);
            $to_date = max($dates);

            $data = [
                'user_id' => $request->user()->id,
                'reason' => $request->reason,
                'from_date' => $from_date,
                'to_date' => $to_date,
                'leave_master_id' => $leave_master->id,
                'session_year_id' => $session_year_id,
                'status' => '0',
            ];

            $leave = Leave::create($data);

            // $leave_details = array();

            foreach ($request->leave_details as $key => $value) {
                $leaveDetail = new LeaveDetail;
                $leaveDetail->leave_id = $leave->id;
                $leaveDetail->date = date('Y-m-d', strtotime($value['date']));
                $leaveDetail->type = $value['type'];
                $leaveDetail->session_year_id = $session_year_id;
                $leaveDetail->save();
            }

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file_upload) {
                    $file = new File;
                    $file->modal_type = "App\Models\Leave";
                    $file->modal_id = $leave->id;
                    $file->file_name = $file_upload->getClientOriginalName();
                    $file->type = 1;
                    $file->file_url = $file_upload->store('leave', 'public');
                    $file->save();
                }
            }
            ResponseService::successResponse('data_store_successfully', $leave ?? '');
        } catch (\Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getMyLeave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12',
            'status' => 'in:0,1,2',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $setting = getSettings();
            $session_year_id = $request->session_year_id ?? $setting['session_year'];
            $leaveMaster = LeaveMaster::where('session_year_id', $session_year_id)->first();
            $sql = Leave::with('leave_detail', 'file')->where('user_id', Auth::user()->id)->withCount(['leave_detail as full_leave' => function ($q) {
                $q->where('type', 'Full');
            }])->withCount(['leave_detail as half_leave' => function ($q) {
                $q->whereNot('type', 'Full');
            }])->whereHas('leave_master', function ($q) use ($session_year_id) {
                $q->where('session_year_id', $session_year_id);
            })->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })->when($request->month, function ($q) use ($request) {
                $q->whereHas('leave_detail', function ($q) use ($request) {
                    $q->whereMonth('date', $request->month);
                });
            })->orderBy('id', 'DESC')->get();

            $sql = $sql->map(function ($sql) {
                $total_leaves = ($sql->half_leave / 2) + $sql->full_leave;
                $sql->days = $total_leaves;

                return $sql;
            });

            $data = [
                'monthly_allowed_leaves' => $leaveMaster->total_leave ?? 0,
                'taken_leaves' => $sql->where('status', 1)->sum('days'),
                'leave_details' => $sql,
            ];
            ResponseService::successResponse('Data Fetched Successfully', $data);
        } catch (\Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteLeave(Request $request)
    {
        try {

            $request->validate([
                'leave_id' => 'required|integer',
            ]);

            $leave = Leave::where('id', $request->leave_id)
                ->where('user_id', Auth::id())
                ->first();

            if (! $leave) {
                return ResponseService::validationError('Leave not found or unauthorized.');
            }

            $leave->delete();

            return ResponseService::successResponse('Data Deleted Successfully');
        } catch (\Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getStudentLeaveList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'nullable|integer|between:1,12',
            'class_section_id' => 'required|integer|exists:class_sections,id',
            'session_year_id' => 'required|integer|exists:session_years,id',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $classSection = ClassSection::find($request->class_section_id);

            if (! $classSection) {
                return ResponseService::errorResponse('Class section not found');
            }

            // Only the class teacher (for that session year) sees the class's leave requests.
            if (! $this->isClassTeacherOf($request->class_section_id, $request->session_year_id)) {
                return ResponseService::errorResponse('You are not assigned to this class section', null, 403);
            }

            $class_id = $classSection->class_id;

            $classHasSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $request->session_year_id
            );

            $teacherSemesterAccess = [];

            /*
            |--------------------------------------------------------------------------
            | Get Teacher Semester Access
            |--------------------------------------------------------------------------
            */
            if ($classHasSemester) {

                $teacherSemesterAccess = ClassTeacher::where(
                    'class_teacher_id',
                    Auth::user()->teacher->id
                )
                    ->where('class_section_id', $request->class_section_id)
                    ->where('session_year_id', $request->session_year_id)
                    ->whereNotNull('semester_id')
                    ->pluck('semester_id')
                    ->toArray();
            }

            /*
            |--------------------------------------------------------------------------
            | Base Leave Query
            |--------------------------------------------------------------------------
            */
            $leaveQuery = Leave::with([
                'leave_detail',
                'user.student',
                'file',
            ])
                ->where('session_year_id', $request->session_year_id)

                // Only Student Users
                ->whereHas('user.roles', function ($query) {
                    $query->where('name', 'Student');
                })

                // Filter Student Session
                ->whereHas('user.student.studentSessions', function ($query) use ($request) {
                    $query
                        ->where('session_year_id', $request->session_year_id)
                        ->where('class_section_id', $request->class_section_id);
                });

            /*
            |--------------------------------------------------------------------------
            | Semester Based Leave Filtering
            |--------------------------------------------------------------------------
            |
            | If teacher has semester access then leave from_date
            | must fall inside ANY of the assigned semester ranges
            |
            */
            if ($classHasSemester && ! empty($teacherSemesterAccess)) {

                $semesters = Semester::whereIn('id', $teacherSemesterAccess)->get();

                $leaveQuery->where(function ($query) use ($semesters) {

                    foreach ($semesters as $semester) {

                        $query->orWhereBetween('from_date', [
                            $semester->start_date,
                            $semester->end_date,
                        ]);
                    }
                });
            }

            /*
            |--------------------------------------------------------------------------
            | Month Filter
            |--------------------------------------------------------------------------
            */
            if (! empty($request->month)) {

                $leaveQuery->whereHas('leave_detail', function ($query) use ($request) {
                    $query->whereMonth('date', $request->month);
                });
            }

            $leaves = $leaveQuery
                ->latest()
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Transform Response
            |--------------------------------------------------------------------------
            */
            $leaves->map(function ($leave) use ($request) {

                $studentSession = $leave->user
                    ->student
                    ->studentSessions()
                    ->where('session_year_id', $request->session_year_id)
                    ->where('class_section_id', $request->class_section_id)
                    ->first();

                // Attach class_section_id directly
                $leave->user->student->class_section_id =
                    optional($studentSession)->class_section_id;

                // Remove studentSessions from response
                unset($leave->user->student->studentSessions);

                // Total Leave Days
                $leave->total_days = $leave->leave_detail->sum(function ($detail) {
                    return $detail->type === 'Full' ? 1 : 0.5;
                });

                return $leave;
            });

            $data = [
                'total_leave_requests' => $leaves->count(),
                'leave_details' => $leaves,
            ];

            return ResponseService::successResponse(
                'Data Fetched Successfully',
                $data
            );

        } catch (\Throwable $e) {

            return ResponseService::errorResponse(
                'error_occurred',
                null,
                103,
                $e
            );
        }
    }

    public function studentLeaveStatusUpdate(Request $request)
    {
        $request->validate([
            'leave_id' => 'required|integer',
            'status' => 'required|in:0,1,2',
            'reason_of_rejection' => 'nullable|string',
        ]);

        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $leave = Leave::findOrFail($request->leave_id);

            $student = Students::with('user')->where('user_id', $leave->user_id)->first();

            // Only a student's leave, decided by the class teacher of the student's class
            // section in the leave's session year.
            $leaveSessionYearId = $leave->session_year_id ?? $session_year_id;
            $classTeacherSectionIds = ClassTeacher::where('class_teacher_id', $this->currentTeacher()->id)
                ->where('session_year_id', $leaveSessionYearId)
                ->pluck('class_section_id');
            if (! $student
                || ! Auth::user()->can('student-leave-approve')
                || $this->studentClassSectionIds($student, $leaveSessionYearId)->intersect($classTeacherSectionIds)->isEmpty()) {
                return ResponseService::errorResponse('no_permission_message', null, 403);
            }

            $fatherId = $student->father_id;
            $motherId = $student->mother_id;
            $guardianId = $student->guardian_id;

            $parentUserIds = Parents::whereIn('id', [
                $fatherId,
                $motherId,
                $guardianId,
            ])->pluck('user_id');

            $leave->status = $request->status;
            $leave->reason_of_rejection = $request->reason_of_rejection;
            $leave->save();

            $title = 'Leave Alert';
            $type = 'leave';
            $image = null;
            $userinfo = null;

            $fullName = $student->user->first_name.' '.$student->user->last_name;

            if ($request->status == 1) {
                $body = "{$fullName} leave has been approved.";
            } elseif ($request->status == 2) {
                if ($request->reason_of_rejection) {
                    $body = "{$fullName} leave is rejected due to {$request->reason_of_rejection}.";
                } else {
                    $body = "{$fullName} leave is rejected.";
                }
            } else {
                return ResponseService::successResponse('data_update_successfully');
            }

            // No mass assignment here
            $notification = new Notification;
            $notification->send_to = 3;
            $notification->session_year_id = $session_year_id;
            $notification->title = $title;
            $notification->message = $body;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();

            $rows = $parentUserIds->map(fn ($id) => [
                'notification_id' => $notification->id,
                'user_id' => $id,
            ])->toArray();

            UserNotification::insert($rows);

            sendSimpleNotification($parentUserIds, $title, $body, $type, $image, $userinfo);

            return ResponseService::successResponse('data_update_successfully');
        } catch (\Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
