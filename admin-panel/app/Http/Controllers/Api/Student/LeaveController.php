<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassTeacher;
use App\Models\File;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\Notification;
use App\Models\StudentSessions;
use App\Models\Teacher;
use App\Models\UserNotification;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Student app: the student's leave requests.
 */
class LeaveController extends Controller
{
    use StudentAccess;

    public function applyLeave(Request $request)
    {
        $allowed = ['reason', 'leave_details', 'files'];

        // reject any extra keys
        $unknown = array_diff(array_keys($request->all()), $allowed);

        if (! empty($unknown)) {
            return ResponseService::validationError('Unknown fields: ' . implode(', ', $unknown));
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
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $class_section_id = StudentSessions::where('student_id', $request->user()->student->id)->where('session_year_id', $session_year_id)->first()->class_section_id;
            $teacher_ids = ClassTeacher::where('class_section_id', $class_section_id)->pluck('class_teacher_id');
            $user = Teacher::whereIn('id', $teacher_ids)->pluck('user_id')->toArray();

            // $sessionYear = SessionYear::where('id', $session_year_id)->first();

            // $public_holiday = Holiday::whereDate('date', '>=', $sessionYear->start_date)->whereDate('date', '<=', $sessionYear->end_date)->get()->pluck('date')->toArray();

            $dates = array_column($request->leave_details, 'date');
            $from_date = min($dates);
            $to_date = max($dates);

            $data = [
                'user_id' => $request->user()->id,
                'reason' => $request->reason,
                'from_date' => $from_date,
                'to_date' => $to_date,
                'leave_master_id' => null,
                'status' => '0',
                'session_year_id' => $session_year_id,
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

            $title = 'Leave Alert';

            $type = 'leave';
            $image = null;
            $userinfo = null;

            $body = $request->user()->first_name . ' ' . $request->user()->last_name . ' ' . 'has added a new leave request';

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

            try {
                sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            } catch (\Exception $e) {
            }

            return ResponseService::successResponse('Data Stored Successfully', $leave ?? '');
        } catch (\Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getMyLeave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12',
            'status' => 'in:0,1,2',
            'session_year_id' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {

            $setting = getSettings();
            $session_year_id = $request->session_year_id ?? $setting['session_year'];

            $sql = Leave::with('leave_detail', 'file')->where('user_id', Auth::user()->id)->where('session_year_id', $session_year_id)->withCount(['leave_detail as full_leave' => function ($q) {
                $q->where('type', 'Full');
            }])->withCount(['leave_detail as half_leave' => function ($q) {
                $q->whereNot('type', 'Full');
            }]);

            if (isset($request->status)) {
                $sql->where('status', $request->status);
            }

            if ($request->month) {
                $sql->whereHas('leave_detail', function ($q) use ($request) {
                    $q->whereMonth('date', $request->month);
                });
            }

            $sql->orderBy('id', 'DESC');
            $sql = $sql->get();

            $sql = $sql->map(function ($sql) {
                $total_leaves = ($sql->half_leave / 2) + $sql->full_leave;
                $sql->days = $total_leaves;

                return $sql;
            });

            $data = [
                'taken_leaves' => $sql->where('status', 1)->sum('days'),
                'leave_details' => $sql,
            ];

            return ResponseService::successResponse('Data Fetched Successfully', $data);
        } catch (\Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
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
}
