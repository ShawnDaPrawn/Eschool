<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassTeacher;
use App\Models\File;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\Notification;
use App\Models\SessionYear;
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
 * Parent app: leave requests for the parent's children.
 */
class LeaveController extends Controller
{
    use ParentAccess;

    public function applyLeave(Request $request)
    {
        $allowed = ['child_id', 'reason', 'leave_details', 'files'];

        // reject any extra keys
        $unknown = array_diff(array_keys($request->all()), $allowed);

        if (! empty($unknown)) {
            return ResponseService::validationError('Unknown fields: '.implode(', ', $unknown));
        }

        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
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
            $parentUserId = Auth::id();

            // Fetch student ONLY if it belongs to logged-in parent
            $student = Students::where('id', $request->child_id)
                ->where(function ($query) use ($parentUserId) {

                    $query->whereHas('father', function ($q) use ($parentUserId) {
                        $q->where('user_id', $parentUserId);
                    })
                        ->orWhereHas('mother', function ($q) use ($parentUserId) {
                            $q->where('user_id', $parentUserId);
                        })
                        ->orWhereHas('guardian', function ($q) use ($parentUserId) {
                            $q->where('user_id', $parentUserId);
                        });
                })
                ->with('user')
                ->first();

            if (! $student) {
                return ResponseService::validationError('Child not found or unauthorized.');
            }

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $student_class_section_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->first()->class_section_id;
            $teacher_ids = ClassTeacher::where('class_section_id', $student_class_section_id)->pluck('class_teacher_id');
            $user = Teacher::whereIn('id', $teacher_ids)->pluck('user_id')->toArray();

            $sessionYear = SessionYear::where('id', $session_year_id)->first();

            $public_holiday = Holiday::whereDate('date', '>=', $sessionYear->start_date)->whereDate('date', '<=', $sessionYear->end_date)->get()->pluck('date')->toArray();

            $dates = array_column($request->leave_details, 'date');
            $from_date = min($dates);
            $to_date = max($dates);

            $data = [
                'user_id' => $student->user_id,
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

            $body = $student->user->first_name.' '.$student->user->last_name.' '.'has added a new leave request';

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
            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);

            return ResponseService::successResponse('Data Stored Successfully', $leave ?? '', [], 200);
        } catch (\Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getMyLeave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
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
            $student = $this->resolveChild($request);

            $sql = Leave::with('leave_detail', 'file')->where('user_id', $student->user_id)->where('session_year_id', $session_year_id)->withCount(['leave_detail as full_leave' => function ($q) {
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

            return ResponseService::successResponse('Data Fetched Successfully', $data, [], 200);
        } catch (\Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteLeave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leave_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            // Only a still-pending leave (status 0) of one of this parent's children: an
            // approved or rejected leave is a school record (the app offers delete only
            // for pending ones).
            $childUserIds = $request->user()->parent->children()->pluck('user_id');

            $leave = Leave::whereKey((int) $request->leave_id)
                ->whereIn('user_id', $childUserIds)
                ->where('status', 0)
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
