<?php

declare(strict_types=1);

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\LeaveMaster;
use App\Models\SessionYear;
use App\Models\Staff;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin panel: leave report.
 */
class ReportController extends Controller
{
    public function leaveReportIndex()
    {
        if (! Auth::user()->can('leave-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $users = null;

        if (Auth::user()->can('leave-approve')) {
            $teachers = Teacher::with('user')->get();
            $staff = Staff::with('user')->get();

            // Combine all users and sort by first name in ascending order
            $users = $teachers->concat($staff)->sortBy(function ($item) {
                return $item->user?->first_name;
            })->values(); // Reset array keys after sorting
        }

        $sessionYears = SessionYear::all();

        $settings = getSettings();
        $currentSessionYearId = $settings['session_year'];

        return view('leave.leave_details', compact('users', 'sessionYears', 'currentSessionYearId'));
    }

    public function leaveDetails(Request $request)
    {
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
            'session_year_id' => 'nullable|integer|exists:session_years,id',
            'staff_id' => 'nullable|integer|exists:users,id',
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

        $session_year_id = $request->session_year_id;

        $staff_id = $request->staff_id;
        if (! $staff_id) {
            $staff_id = Auth::user()->id;
        }

        $leaveMaster = LeaveMaster::with('session_year')->where('session_year_id', $session_year_id)->first();

        // Get months starting from session year
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

        $bulkData = [];
        $bulkData['total'] = count($months);
        $rows = [];
        $no = 1;

        foreach ($months as $key => $month) {
            DB::enableQueryLog();
            $leaves = LeaveDetail::whereMonth('date', $key)
                ->whereHas('leave', function ($q) use ($session_year_id, $staff_id) {
                    $q->where('user_id', $staff_id)->where('status', 1)
                        ->whereHas('leave_master', function ($q) use ($session_year_id) {
                            $q->where('session_year_id', $session_year_id);
                        });
                })->get();

            $allocated = 0;
            $total_used_leaves = 0;

            if ($leaveMaster) {

                $tempRow['allocated'] = $leaveMaster->total_leave;
                $allocated = $leaveMaster->total_leave;
            }
            $tempRow['lwp'] = '-';
            $lwp = 0;
            $total_leaves = $leaves->count();
            // dd($total_leaves);
            $total_used_leaves = $total_leaves - ($leaves->where('type', '!=', 'Full')->count() / 2);

            if ($allocated < $total_used_leaves) {
                $lwp = $total_used_leaves - $allocated;
                $tempRow['lwp'] = $lwp;
                $tempRow['used_cl'] = $total_used_leaves - $lwp;
            } else {
                $tempRow['used_cl'] = '-';
                if ($total_used_leaves) {
                    $tempRow['used_cl'] = $total_used_leaves;
                }
            }
            $tempRow['total'] = '-';
            if ($total_used_leaves) {
                $tempRow['total'] = $total_used_leaves;
            }

            if ($total_used_leaves >= $allocated) {
                $tempRow['remaining_cl'] = '-';
                $tempRow['remaining_total'] = '-';
            } else {
                $tempRow['remaining_cl'] = $total_used_leaves != 0 ? $allocated - $total_used_leaves : '-';
                $tempRow['remaining_total'] = $total_used_leaves != 0 ? $allocated - $total_used_leaves : '-';
            }

            $tempRow['no'] = $no++;
            $tempRow['month'] = $month;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }
}
