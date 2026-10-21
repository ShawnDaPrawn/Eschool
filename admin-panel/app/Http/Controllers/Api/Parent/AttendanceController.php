<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StudentSessions;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Parent app: a child's attendance and the attendance report.
 */
class AttendanceController extends Controller
{
    use ParentAccess;

    /**
     * @param
     * month : 4 //OPTIONAL
     * year : 2022 //OPTIONAL
     */
    public function getAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'month' => 'nullable|numeric',
            'year' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $children = $this->resolveChild($request);
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $month = $request->filled('month') ? (int) $request->month : null;
            $year = $request->filled('year') ? (int) $request->year : null;

            $data = Attendance::monthlyReport($children->id, (int) $session_year_id, $month, $year);
            ResponseService::successResponse('Data fetched successfully.', $data, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function attendanceReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_year_id' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'child_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $child = $this->resolveChild($request);
        $studentSession = StudentSessions::where('student_id', $child->id)
            ->where('session_year_id', (int) $request->session_year_id)
            ->first();

        if (!$studentSession) {
            ResponseService::errorResponse(
                'No session found for this student in the current session year',
                null,
                113
            );
        }
            $data = Attendance::monthlyReport($child->id, (int) $request->session_year_id, (int) $request->month);
            
            ResponseService::successResponse('Data fetched successfully.', $data);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
