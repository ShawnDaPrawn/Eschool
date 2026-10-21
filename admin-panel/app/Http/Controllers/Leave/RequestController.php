<?php

declare(strict_types=1);

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\LeaveMaster;
use App\Models\SessionYear;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Admin panel: staff leave requests: review, approve or reject, and enter leave for someone.
 */
class RequestController extends Controller
{
    public function leaveRequestIndex()
    {
        if (! Auth::user()->can('leave-approve')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $sessionYears = SessionYear::all();

        $settings = getSettings();
        $currentSessionYearId = $settings['session_year'];

        $currentSessionYear = SessionYear::where('id', $currentSessionYearId)->first();

        $leaveMaster = LeaveMaster::where('session_year_id', $currentSessionYearId)->first();
        $holiday_days = '';

        if ($leaveMaster) {
            $holiday_days = $leaveMaster->holiday_days;
        }

        $teachers = Teacher::with('user')->get();
        $staff = Staff::with('user')->get();

        // Combine all users and sort by first name in ascending order
        $users = $teachers->concat($staff)->sortBy(function ($item) {
            return $item->user?->first_name;
        })->values(); // Reset array keys after sorting

        $modalUsers = User::whereHas('roles', function ($q) {
            $q->whereNotIn('name', ['student', 'parent', 'Guardian']);
        })
            ->orderBy('first_name', 'asc')
            ->get(['id', 'first_name', 'last_name']);

        $months = [
            0 => trans('All'),
            1 => trans('January'),
            2 => trans('February'),
            3 => trans('March'),
            4 => trans('April'),
            5 => trans('May'),
            6 => trans('June'),
            7 => trans('July'),
            8 => trans('August'),
            9 => trans('September'),
            10 => trans('October'),
            11 => trans('November'),
            12 => trans('December'),
        ];

        $holiday = Holiday::where('session_year_id', $currentSessionYear->id)->pluck('date')->toArray();

        $public_holiday = implode(',', $holiday);

        return view('leave.leave_request', compact('sessionYears', 'currentSessionYearId', 'currentSessionYear', 'holiday_days', 'users', 'months', 'public_holiday', 'modalUsers', 'leaveMaster'));
    }

    public function leaveRequestShow(Request $request)
    {
        if (! Auth::user()->can('leave-approve')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        // Validate request payload
        $validator = Validator::make($request->all(), [
            'offset' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|string|in:id,from_date,to_date,reason,status,created_at,updated_at',
            'order' => 'nullable|string|in:ASC,DESC,asc,desc',
            'search' => 'nullable|string|max:255',
            'session_year_id' => 'nullable|integer|exists:session_years,id',
            'filter_upcoming' => 'nullable|string|in:All,Today,Tomorrow,Upcoming',
            'month_id' => 'nullable|integer|min:0|max:12',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => trans('validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        // $offset = 0;
        // $limit = 10;
        // $sort = 'id';
        // $order = 'DESC';
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        $search = $request->search;
        $session_year_id = $request->session_year_id;
        $filter_upcoming = $request->filter_upcoming;
        $month_id = $request->month_id;
        $user_id = $request->user_id;

        $sql = Leave::with('leave_detail', 'user', 'file')
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")->orwhere('reason', 'LIKE', "%$search%")->orwhere('from_date', 'LIKE', "%$search%")->orwhere('to_date', 'LIKE', "%$search%")->orwhereHas('user', function ($q) use ($search) {
                            $q->whereRaw('concat(first_name," ",last_name) like ?', "%$search%");
                        });
                    });
                });
            });

        if ($session_year_id) {
            $sql->whereHas('leave_master', function ($q) use ($session_year_id) {
                $q->where('session_year_id', $session_year_id);
            });
        }

        if ($filter_upcoming != 'All') {
            if ($filter_upcoming == 'Today') {
                $sql->whereDate('from_date', '<=', Carbon::now()->format('Y-m-d'))->whereDate('to_date', '>=', Carbon::now()->format('Y-m-d'));
            }
            if ($filter_upcoming == 'Tomorrow') {
                $tomorrow_date = Carbon::now()->addDay()->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($tomorrow_date) {
                    $q->whereDate('date', '<=', $tomorrow_date)->whereDate('date', '>=', $tomorrow_date);
                });
            }
            if ($filter_upcoming == 'Upcoming') {
                $upcoming_date = Carbon::now()->addDays(1)->format('Y-m-d');
                $sql->whereHas('leave_detail', function ($q) use ($upcoming_date) {
                    $q->whereDate('date', '>', $upcoming_date);
                });
            }
        }

        if ($month_id) {
            $sql->whereHas('leave_detail', function ($q) use ($month_id) {
                $q->whereMonth('date', $month_id);
            });
        }

        if ($user_id) {
            $sql->where('user_id', $user_id);
        }

        $sql = $sql->withCount(['leave_detail as full_leave' => function ($q) {
            $q->where('type', 'Full');
        }]);

        $sql = $sql->withCount(['leave_detail as half_leave' => function ($q) {
            $q->whereNot('type', 'Full');
        }]);
        $total = $sql->count();

        // $sql->orderBy('id', 'DESC')->skip($offset)->take($limit);
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $no = 1;

        foreach ($res as $row) {
            $operate = '<a href='.route('leave-status.update', $row->id).' class="btn btn-xs btn-gradient-info btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-eye"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href='.route('leave.destroy', $row->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id='.$row->id.'><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->user->first_name.' '.$row->user->last_name;
            $tempRow['from_date'] = date('d-m-Y', strtotime($row->from_date));
            $tempRow['to_date'] = date('d-m-Y', strtotime($row->to_date));
            $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
            $tempRow['leave_detail'] = $row->leave_detail;
            $tempRow['file'] = $row->file;
            $tempRow['reason'] = $row->reason;
            $tempRow['status'] = $row->status;
            $tempRow['operate'] = $operate;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function leaveStatusUpdate(Request $request)
    {
        if (! Auth::user()->can('leave-approve')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $request->validate([
            'status' => 'required',
        ]);

        try {

            $leave = Leave::findOrFail($request->id);
            // Prevent status changes once finalized (Accepted/Rejected)
            if (in_array((string) $leave->status, ['1', '2'], true)) {
                return response()->json([
                    'error' => true,
                    'message' => trans('leave_status_already_finalized'),
                ]);
            }
            $leave->status = $request->status;
            $leave->save();
            if ((string) $leave->status === '1') {
                $this->markLeaveAsAttendance($leave);
            }

            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'exception' => $e,
            ];
        }

        return response()->json($response);
    }

    public function adminLeaveStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('leave-approve');
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|integer|exists:users,id',
            'reason' => 'required',
            'from_date' => 'required',
            'to_date' => 'required|after_or_equal:from_date',
            'type.*' => 'required|array',
            'files.*' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $leaveMaster = LeaveMaster::where('session_year_id', $session_year_id)->first();

            if (! $leaveMaster) {
                return response()->json([
                    'error' => true,
                    'message' => __('Please configure leave settings for the current session year before creating a leave.'),
                ]);
            }

            if ($request->type) {
                $leave = Leave::create([
                    'user_id' => $request->staff_id,
                    'reason' => $request->reason,
                    'from_date' => date('Y-m-d', strtotime($request->from_date)),
                    'to_date' => date('Y-m-d', strtotime($request->to_date)),
                    'leave_master_id' => $leaveMaster->id,
                    'session_year_id' => $session_year_id ?? null,
                    'status' => '1',
                ]);
            }

            $debugTypes = []; // ← temporary debug, remove once confirmed working

            if ($request->has('type') && is_array($request->type)) {

                foreach ($request->type as $key => $type) {
                    $leaveDetail = new LeaveDetail;
                    $leaveDetail->leave_id = $leave->id;
                    $leaveDetail->date = date('Y-m-d', strtotime($key));
                    $leaveDetail->type = is_array($type) ? ($type[0] ?? 'Full') : $type;
                    $leaveDetail->save();

                    // ← temporary debug: capture exactly what got saved
                    $debugTypes[$key] = [
                        'raw_request_value' => $type,
                        'saved_type' => $leaveDetail->type,
                    ];
                }

                $leave->load('leave_detail');

                $this->markLeaveAsAttendance($leave);

            } else {
                return response()->json([
                    'error' => true,
                    'message' => trans('please_pick_leave_type_for_selected_dates'),
                ], 422);
            }

            if ($request->hasfile('files')) {
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

            return response()->json([
                'error' => false,
                'message' => trans('data_store_successfully'),
                'debug_types' => $debugTypes, // ← temporary debug, remove once confirmed working
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage(), 'line' => $e->getLine()]);
        }
    }

    private function markLeaveAsAttendance(Leave $leave)
    {
        $session_year_id = $leave->session_year_id;

        $details = LeaveDetail::where('leave_id', $leave->id)->get();

        $data = [];

        foreach ($details as $detail) {

            $date = $detail->date;

            $leaveType = 'absent';

            if ($detail->type) {
                $normalized = strtolower(trim($detail->type));

                if (str_contains($normalized, 'second half')) {
                    $leaveType = 'second_half';

                } elseif (str_contains($normalized, 'first half')) {
                    $leaveType = 'first_half';
                }
            }

            $attendance = StaffAttendance::where('user_id', $leave->user_id)
                ->whereDate('date', $date)
                ->first();

            if ($attendance) {

                if (! in_array($attendance->type, ['present', 'holiday'])) {
                    $attendance->update([
                        'type' => $leaveType,
                        'remark' => $leave->reason ?? 'Leave',
                    ]);
                }

            } else {

                $data[] = [
                    'user_id' => $leave->user_id,
                    'session_year_id' => $session_year_id,
                    'date' => $date,
                    'type' => $leaveType,
                    'remark' => $leave->reason ?? 'Leave',
                ];
            }
        }

        if (! empty($data)) {
            StaffAttendance::insert($data);
        }
    }
}
