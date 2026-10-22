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
use App\Models\Teacher;
use App\Models\User;
use App\Services\Platform\Files\FileNames;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Admin panel: the signed-in user's own leave.
 */
class LeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! Auth::user()->can('leave-create')) {
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

        $months = [
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

        return view('leave.index', compact(
            'sessionYears',
            'currentSessionYearId',
            'currentSessionYear',
            'holiday_days',
            'users',
            'months',
            'public_holiday',
            'leaveMaster'
        ));
    }

    public function store(Request $request)
    {

        if (! Auth::user()->can('leave-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'reason' => 'required',
                'from_date' => 'required',
                'to_date' => 'required|after_or_equal:from_date',
                'leave_master_id' => 'required',
                'type.*' => 'required|array',
                'files.*' => 'nullable',
            ]

        );

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $leaveMaster = LeaveMaster::where('session_year_id', $session_year_id)->first();

            $leaveDate = date('Y-m-d', strtotime($request->from_date));
            $dayName = date('l', strtotime($leaveDate));

            if ($leaveMaster && $leaveMaster->holiday_days == $dayName) {
                return response()->json([
                    'error' => true,
                    'message' => trans('you_cannot_apply_leave'),
                ]);
            }

            if (Holiday::whereDate('date', $leaveDate)->exists()) {
                return response()->json([
                    'error' => true,
                    'message' => trans('you_cannot_apply_leave_holiday'),
                ]);
            }
            if ($request->type) {
                $data = [
                    'user_id' => Auth::user()->id,
                    'reason' => $request->reason,
                    'from_date' => date('Y-m-d', strtotime($request->from_date)),
                    'to_date' => date('Y-m-d', strtotime($request->to_date)),
                    'leave_master_id' => $request->leave_master_id,
                    'session_year_id' => $session_year_id ?? null,
                    'status' => '0',
                ];

                $leave = Leave::create($data);

                $leaveDetail = [];

                foreach ($request->type as $key => $type) {

                    $leaveDetail = new LeaveDetail;
                    $leaveDetail->leave_id = $leave->id;
                    $leaveDetail->date = date('Y-m-d', strtotime($key));
                    $leaveDetail->type = $type[0];
                    $leaveDetail->save();
                }

                if ($request->hasFile('files')) {
                    foreach ($request->file('files') as $file_upload) {
                        $file = new File;
                        $file->modal_type = "App\Models\Leave";
                        $file->modal_id = $leave->id;
                        $file->file_name = FileNames::forUpload($file_upload);
                        $file->type = 1;
                        $file->file_url = $file_upload->store('leave', 'public');
                        $file->save();
                    }
                }

                $response = [
                    'error' => false,
                    'message' => trans('data_store_successfully'),
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => trans('please_select_leave_type'),
                ];
            }
        } catch (\Throwable $e) {
            report($e);
            DB::rollback();
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'exception' => $e,
            ];
        }

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        // dd($request->all());
        if (! Auth::user()->can('leave-list')) {
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
            'filter_upcoming' => 'nullable|string|in:Today,Tomorrow,Upcoming,All',
            'month_id' => 'nullable|integer|min:0|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => trans('validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

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

        $sql = Leave::with('leave_detail', 'file')->where('user_id', Auth::user()->id)
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")->orwhere('reason', 'LIKE', "%$search%")->orwhere('from_date', 'LIKE', "%$search%")->orwhere('to_date', 'LIKE', "%$search%");
                    });
                });
            });

        if ($session_year_id) {
            $sql->whereHas('leave_master', function ($q) use ($session_year_id) {
                $q->where('session_year_id', $session_year_id);
            });
        }

        $sql = $sql->withCount(['leave_detail as full_leave' => function ($q) {
            $q->where('type', 'Full');
        }]);

        $sql = $sql->withCount(['leave_detail as half_leave' => function ($q) {
            $q->whereNot('type', 'Full');
        }]);

        if ($filter_upcoming) {
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

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $no = 1;

        foreach ($res as $row) {

            $operate = '';
            $operate = '<a href='.route('leave-status.update', $row->id).' class="btn btn-xs btn-gradient-info btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-eye"></i></a>&nbsp;&nbsp;';

            if ($row->status == 0) {
                $operate .= '<a href='.route('leave.destroy', $row->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id='.$row->id.'><i class="fa fa-trash"></i></a>';
            }
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
            $tempRow['from_date'] = date('d-m-Y', strtotime($row->from_date));
            $tempRow['to_date'] = date('d-m-Y', strtotime($row->to_date));
            $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
            $tempRow['reason'] = $row->reason;
            $tempRow['status'] = $row->status;
            $tempRow['leave_detail'] = $row->leave_detail;
            $tempRow['file'] = $row['file'];
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        // dd($bulkData);
        return response()->json($bulkData);
    }

    public function destroy($id)
    {
        if (! Auth::user()->can('leave-delete')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        try {
            $leave = Leave::find($id);

            if ($leave->file) {
                foreach ($leave->file as $file) {
                    if (Storage::disk('public')->exists($file->file_url)) {
                        Storage::disk('public')->delete($file->file_url);
                    }
                }
            }
            $leave->file()->delete();

            $leave->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e,
            ];
        }

        return response()->json($response);
    }
}
