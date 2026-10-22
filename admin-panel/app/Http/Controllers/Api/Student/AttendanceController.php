<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StudentSessions;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Student app: attendance records and the attendance report.
 */
class AttendanceController extends Controller
{
    use StudentAccess;

    /**
     * @param
     * month : 4 //OPTIONAL
     * year : 2022 //OPTIONAL
     */
    public function getAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'nullable|numeric',
            'year' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $request->user()->student;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $month = $request->filled('month') ? (int) $request->month : null;
            $year = $request->filled('year') ? (int) $request->year : null;

            $data = Attendance::monthlyReport($student->id, (int) $session_year_id, $month, $year);

            return ResponseService::successResponse('Data fetched successfully.', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function attendanceReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_year_id' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $request->user()->student;
            $studentSession = StudentSessions::where('student_id', $student->id)
                ->where('session_year_id', (int) $request->session_year_id)
                ->first();

            if (! $studentSession) {
                return ResponseService::errorResponse(
                    'No session found for this student in the current session year',
                    null,
                    113
                );
            }
            $data = Attendance::monthlyReport($student->id, (int) $request->session_year_id, (int) $request->month);

            return ResponseService::successResponse('Data fetched successfully.', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
