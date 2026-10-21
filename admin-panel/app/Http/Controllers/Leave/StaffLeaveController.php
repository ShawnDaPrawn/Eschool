<?php

declare(strict_types=1);

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveMaster;
use App\Models\SessionYear;
use App\Models\Staff;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin panel: the list of staff leave.
 */
class StaffLeaveController extends Controller
{
    public function staffLeaveIndex()
    {
        if (! Auth::user()->can('staff-leave-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $teacher_id = Auth::user()->teacher->id;
        $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher_id)->pluck('class_section_id');
        $class_sections = ClassSection::with('class.medium', 'section', 'classTeachers', 'class.streams')->whereIn('id', $class_section_ids)->get();

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

        $users = $teachers->merge($staff);

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

        return view('leave.staff_leave', compact('sessionYears', 'currentSessionYearId', 'currentSessionYear', 'holiday_days', 'users', 'months', 'public_holiday', 'class_sections'));
    }

    public function staffLeaveList(Request $request)
    {
        if (! Auth::user()->can('staff-leave-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
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

        $user = Auth::user();

        $sql = Leave::with('leave_detail', 'user', 'file')->where('user_id', '!=', $user->id)->where('status', 1)
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")->orwhere('reason', 'LIKE', "%$search%")->orwhere('from_date', 'LIKE', "%$search%")->orwhere('to_date', 'LIKE', "%$search%");
                    });
                });
            });

        if ($session_year_id) {
            $sql->where('session_year_id', $session_year_id);
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

        $sql = $sql->withCount(['leave_detail as full_leave' => function ($q) {
            $q->where('type', 'Full');
        }]);

        $sql = $sql->withCount(['leave_detail as half_leave' => function ($q) {
            $q->whereNot('type', 'Full');
        }]);
        $total = $sql->count();

        $sql->orderBy('id', 'DESC')->skip($offset)->take($limit);
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
            $tempRow['user_id'] = $row->user->id;
            $tempRow['name'] = $row->user->first_name.' '.$row->user->last_name;
            $tempRow['from_date'] = date('d-m-Y', strtotime($row->from_date));
            $tempRow['to_date'] = date('d-m-Y', strtotime($row->to_date));
            $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
            $tempRow['leave_detail'] = $row->leave_detail;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }
}
