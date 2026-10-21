<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\Notification;
use App\Models\Parents;
use App\Models\StaffAttendance;
use App\Models\Students;
use App\Models\UserNotification;
use App\Services\AbsenceAlertService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Teacher app: taking and reviewing class attendance.
 */
class AttendanceController extends Controller
{
    use TeacherAccess;

    public function getAttendanceReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $month = (int) $request->month;
            $year = (int) $request->year;

            // A teacher's attendance is simply their own staff_attendances rows,
            // keyed by user_id (teachers and staff are separate; attendance is per-user).
            $records = StaffAttendance::where('user_id', Auth::id())
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->orderBy('date')
                ->get(['date', 'type']);

            // StaffAttendance.type is an enum string; 'holiday' is intentionally absent
            // (excluded from the array and all counts). 'leave' counts as full-day absent.
            $map = [
                'present' => 0,
                'absent' => 1,
                'leave' => 1,
                'first_half' => 2,
                'second_half' => 3,
            ];

            $counts = [
                'full_day_present' => 0,
                'full_day_absent' => 0,
                'first_half_absent' => 0,
                'second_half_absent' => 0,
            ];
            $attendance = [];
            $markedDates = [];

            foreach ($records as $rec) {
                if (! array_key_exists($rec->type, $map)) {
                    continue; // holiday / unknown
                }
                $code = $map[$rec->type];
                $date = Carbon::parse($rec->date)->format('Y-m-d');
                $attendance[] = [
                    'date' => $date,
                    'type' => $code,
                ];
                match ($code) {
                    0 => $counts['full_day_present']++,
                    1 => $counts['full_day_absent']++,
                    2 => $counts['first_half_absent']++,
                    3 => $counts['second_half_absent']++,
                };
                $markedDates[] = $date;
            }

            // Approved leave days without an explicit staff_attendances row also count as absent.
            $leaveDetails = LeaveDetail::whereHas('leave', function ($query) {
                $query->where('user_id', Auth::id())->where('status', 1);
            })
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get(['date', 'type']);

            foreach ($leaveDetails as $detail) {
                $date = Carbon::parse($detail->date)->format('Y-m-d');
                if (in_array($date, $markedDates, true)) {
                    continue; // an explicit staff_attendances row for this date takes precedence
                }

                $code = match (Attendance::leaveTypeToType($detail->type)) {
                    Attendance::TYPE_FIRST_HALF => 2,
                    Attendance::TYPE_SECOND_HALF => 3,
                    default => 1,
                };

                $attendance[] = ['date' => $date, 'type' => $code];
                match ($code) {
                    1 => $counts['full_day_absent']++,
                    2 => $counts['first_half_absent']++,
                    3 => $counts['second_half_absent']++,
                };
                $markedDates[] = $date;
            }

            usort($attendance, fn ($a, $b) => $a['date'] <=> $b['date']);

            // Each half-day absence is 0.5 present + 0.5 absent (the other half of the
            // day was attended), matching Attendance::monthlyReport()'s totals rule.
            $halfDayCount = $counts['first_half_absent'] + $counts['second_half_absent'];

            $data = [
                'total_present' => $counts['full_day_present'] + ($halfDayCount * 0.5),
                'total_absent' => $counts['full_day_absent'] + ($halfDayCount * 0.5),
                'full_day_present' => $counts['full_day_present'],
                'full_day_absent' => $counts['full_day_absent'],
                'first_half_absent' => $counts['first_half_absent'],
                'second_half_absent' => $counts['second_half_absent'],
                'attendance' => $attendance,
            ];

            ResponseService::successResponse('Data fetched successfully.', $data);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getAttendance(Request $request)
    {
        ResponseService::noPermissionThenSendJson('attendance-list');

        $class_section_id = $request->class_section_id;
        $attendance_type = $request->type;

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'date' => 'required|date',
            'type' => 'in:0,1,3,4,5',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        $date = date('Y-m-d', strtotime($request->date));

        try {
            if (! $this->isClassTeacherOf($class_section_id)) {
                ResponseService::errorResponse('You are not assigned to this class section', null, 403);
            }

            $studentsInClass = Students::where('class_section_id', $class_section_id)->get(['id', 'user_id']);

            // Approved leaves for the day, translated to the 1=Full/2=First Half/3=Second
            // Half code the leave_details response expects (mirrors the locking rule in
            // submitAttendance, which reads the same Leave::approvedTypesForDate()).
            $leaveTypeByUserId = Leave::approvedTypesForDate($date);
            $leave_details = $studentsInClass
                ->filter(fn ($student) => isset($leaveTypeByUserId[$student->user_id]))
                ->map(function ($student) use ($leaveTypeByUserId) {
                    $leave_type = match (Attendance::leaveTypeToType($leaveTypeByUserId[$student->user_id])) {
                        Attendance::TYPE_FIRST_HALF => 2,
                        Attendance::TYPE_SECOND_HALF => 3,
                        default => 1,
                    };

                    return ['student_id' => $student->id, 'leave_type' => $leave_type];
                })
                ->values()
                ->toArray();

            $sql = Attendance::where('class_section_id', $class_section_id)->where('date', $date);
            if (isset($attendance_type) && $attendance_type != '') {
                $sql->where('type', $attendance_type);
            }
            $data = $sql->get();

            // Roster scheme: 0=Absent, 1=Present, 2=First Half Absent, 3=Second Half
            // Absent. Absent/Present already match the DB values; only the half-day
            // types need remapping. A day marked Holiday has no slot in this 4-state
            // scheme, so it collapses to Present - `is_holiday` below is what actually
            // flags the day, not the per-student type.
            $data->each(function ($attendance) {
                $attendance->type = match ((int) $attendance->type) {
                    Attendance::TYPE_FIRST_HALF => 2,
                    Attendance::TYPE_SECOND_HALF => 3,
                    Attendance::TYPE_HOLIDAY => 1,
                    default => (int) $attendance->type,
                };
            });

            $holiday = Holiday::where('date', $date)->get();
            if ($holiday->count()) {
                ResponseService::successResponse('data_update_successfully', [
                    'is_holiday' => true,
                    'holiday' => $holiday,
                    'leave_details' => $leave_details,
                    'attendance_reports' => $data,
                ]);
            } else {
                if ($data->count()) {
                    ResponseService::successResponse('Data Fetched Successfully', [
                        'is_holiday' => false,
                        'leave_details' => $leave_details,
                        'attendance_reports' => $data,
                    ]);
                } else {
                    ResponseService::successResponse('Attendance not recorded', [
                        'is_holiday' => false,
                        'holiday' => ($holiday->count() == 0) ? null : $holiday,
                        'leave_details' => $leave_details,
                        'attendance_reports' => $data,
                    ], );
                }
            }
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function submitAttendance(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['attendance-create', 'attendance-edit']);

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            // 'student_id' => 'required',
            'attendance.*.student_id' => 'required',
            'attendance.*.type' => 'required|in:0,1,2,3',
            'date' => 'required|date',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $class_section_id = $request->class_section_id;

            if (! $this->isClassTeacherOf($class_section_id, $session_year_id)) {
                ResponseService::errorResponse('You are not assigned to this class section', null, 403);
            }

            // Attendance can only be recorded for students of this class section.
            if (! $this->allStudentsInClassSection(collect($request->attendance)->pluck('student_id'), $class_section_id)) {
                ResponseService::errorResponse('no_permission_message', null, 403);
            }

            $date = date('Y-m-d', strtotime($request->date));
            $getid = Attendance::where(['date' => $date, 'class_section_id' => $class_section_id])->get();

            // Approved leaves lock the attendance type for the date (matched by user_id);
            // the leave must be rejected to change it.
            $leaveTypeByUserId = Leave::approvedTypesForDate($date);
            $studentUserIds = Students::whereIn('id', collect($request->attendance)->pluck('student_id'))->pluck('user_id', 'id');

            foreach ($request->attendance as $attendance) {
                $studentAttendance = $getid->first(function ($query) use ($attendance) {
                    return $query->student_id == $attendance['student_id'];
                });

                $studentUserId = $studentUserIds[$attendance['student_id']] ?? null;
                // Parents hear about an absence once, not on every re-submission.
                $wasAbsent = $studentAttendance && (int) $studentAttendance->type === Attendance::TYPE_ABSENT;

                if ($request->holiday != '' && $request->holiday == 3) {
                    $type = $request->holiday;
                } elseif ($studentUserId !== null && isset($leaveTypeByUserId[$studentUserId])) {
                    $type = Attendance::leaveTypeToType($leaveTypeByUserId[$studentUserId]);
                    if ($type == 0 && ! $wasAbsent) {
                        $absent_student_ids[] = $attendance['student_id'];
                    }
                } else {
                    // Payload uses the roster scheme (0=Absent, 1=Present, 2=First Half
                    // Absent, 3=Second Half Absent); only the half-day codes differ from
                    // the DB's own `type` values (see Attendance::TYPE_FIRST_HALF/SECOND_HALF).
                    $type = match ((int) $attendance['type']) {
                        2 => Attendance::TYPE_FIRST_HALF,
                        3 => Attendance::TYPE_SECOND_HALF,
                        default => (int) $attendance['type'],
                    };
                    if ($type == Attendance::TYPE_ABSENT && ! $wasAbsent) {
                        $absent_student_ids[] = $attendance['student_id'];
                    }
                }
                $attendanceData[] = [
                    'id' => $studentAttendance->id ?? null,
                    'class_section_id' => $class_section_id,
                    'student_id' => $attendance['student_id'],
                    'session_year_id' => $session_year_id,
                    'date' => $date,
                    'type' => $type,
                    'status' => 1,
                ];
            }
            Attendance::upsert($attendanceData, ['id'], ['class_section_id', 'student_id', 'session_year_id', 'date', 'type', 'status']);
            // Send Notification to parents
            // One alert per absent student, to each of their parents and guardian
            // (it used to save only the last student's and skip guardians).
            app(AbsenceAlertService::class)->notify($absent_student_ids ?? [], $date, (int) $session_year_id);
            ResponseService::successResponse('data_store_successfully');
        } catch (Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
