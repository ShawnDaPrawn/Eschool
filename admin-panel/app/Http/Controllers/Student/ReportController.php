<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Category;
use App\Models\ClassSection;
use App\Models\Exam;
use App\Models\ExamMarks;
use App\Models\FeesClass;
use App\Models\FeesPaid;
use App\Models\FormField;
use App\Models\Grade;
use App\Models\Holiday;
use App\Models\InstallmentFee;
use App\Models\Leave;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestionChoice;
use App\Models\OnlineExamStudentAnswer;
use App\Models\PaidInstallmentFee;
use App\Models\PaymentTransaction;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin panel: a student's report (attendance, fees, leave, assignments, exams).
 */
class ReportController extends Controller
{
    public function detail()
    {
        if (! Auth::user()->can('student-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $is_restricted_teacher = Auth::user()->hasRole('Teacher') && ! Auth::user()->can('student-create');

        // For restricted teachers, class sections will be loaded dynamically via AJAX based on session year
        if ($is_restricted_teacher) {
            $class_section = collect();
        } else {
            $class_section = ClassSection::with('class', 'section', 'class.medium', 'class.streams')->get();
        }

        $category = Category::where('status', 1)->get();
        $formFields = FormField::where('for', 1)->orderBy('rank', 'ASC')->get();
         $session_years = SessionYear::orderBy('id', 'ASC')->get();
        
        

        return view('students.stu-detail', compact('class_section', 'category', 'formFields', 'session_years', 'is_restricted_teacher'));
    }

    public function showStudentReport(Request $request, $id)
    {
        ResponseService::noPermissionThenRedirect('student-report');
        $tab = $request->get('tab', 'personal');

        $student = $this->getStudent($id);

        $data = $this->defaultReportData($student);

        if ($tab == 'personal') {
            return $this->response($request, $tab, $data);
        }

        $sessionYear = $this->getSelectedSessionYear($request);

        if (! $sessionYear) {
            return $this->response($request, $tab, $data);
        }

        $data['sessionYearId'] = $sessionYear->id;

        $studentSession = $this->getStudentSession($student->id, $sessionYear->id);

        switch ($tab) {

            case 'fees':
                return $this->feesReport(
                    $request,
                    $student,
                    $studentSession,
                    $sessionYear,
                    $data
                );

            case 'leave':
                return $this->leaveReport(
                    $request,
                    $student,
                    $sessionYear,
                    $data
                );

            case 'exam':
                return $this->examReport(
                    $request,
                    $student,
                    $sessionYear,
                    $data
                );

            case 'assignment':
                return $this->assignmentReport(
                    $request,
                    $student,
                    $sessionYear,
                    $data
                );

            default:
                return $this->attendanceReport(
                    $request,
                    $student,
                    $studentSession,
                    $sessionYear,
                    $data
                );
        }
    }

    public function studentAttendanceReport($id)
    {
        return redirect()->route('student.report', $id.'?tab=attendance');
    }

    private function getstudent($id)
    {
        return $student = Students::with(['user', 'guardian', 'class_section'])
            ->findOrFail($id);

    }

    private function defaultReportData($student)
    {
        return [
            'student' => $student,
            'attendance' => collect(),
            'feesDetail' => collect(),
            'examDetail' => collect(),
            'leaveDetail' => collect(),
            'assignment' => collect(),
            'semesters' => collect(),
            'mounth' => [],
            'holidayscount' => 0,
            'workingdays' => 0,
            'presentday' => 0,
            'absentCount' => 0,
            'firstHalfAbsentCount' => 0,
            'secondHalfAbsentCount' => 0,
            'calendarMonth' => date('m'),
            'calendarYear' => date('Y'),
            'sessionYearId' => null,
        ];
    }

    private function getSelectedSessionYear(Request $request)
    {
        $currentSessionYearId = $request->session_year_id
            ?: (getSettings('session_year')['session_year'] ?? null);

        return $sessionYear = SessionYear::find($currentSessionYearId);
       
    }

    private function getStudentSession($studentId, $sessionYearId)
    {
        return StudentSessions::where('student_id', $studentId)
            ->where('session_year_id', $sessionYearId)
            ->latest()
            ->first();
    }

    // private function attendanceReport(Request $request, $student, $studentSession, $sessionYear, $data)
    // {

    //     [$start, $end] = [
    //         $sessionYear->start_date,
    //         $sessionYear->end_date,
    //     ];
    //     $totalDays = \Carbon\Carbon::parse($start)
    //         ->diffInDays(\Carbon\Carbon::parse($end)) + 1;

    //     // $totalDays = now($start)->diffInDays($end) + 1;

    //     $holidays = Holiday::where('session_year_id', $sessionYear->id)
    //         ->whereBetween('date', [$start, $end])
    //         ->count();

    //     $sunday = 0;
    //     $currentdate = \Carbon\Carbon::parse($start);
    //     while ($currentdate <= \Carbon\Carbon::parse($end)) {
    //         if ($currentdate->isSunday()) {
    //             $sunday++;
    //         }
    //         $currentdate->addday();
    //     }

    //     $sundayes = $holidays + $sunday;

    //     $data['workingdays'] = $totalDays - $sundayes;
    //     $data['holidayscount'] = $holidays;

    //     // months list — always the selected session year's own start/end range.
    //     $monthsList = collect($this->generateMonths($start, $end));
    //     $data['mounth'] = $monthsList;

    //     if ($request->month) {
    //         $data['calendarMonth'] = $request->month;
    //         $data['calendarYear'] = date('Y', strtotime($start));
    //     } else {
    //         // On a fresh load, default to the current running month — but only
    //         // when it actually falls within the selected session year; otherwise
    //         // fall back to the session year's own start month.
    //         $currentMonth = $monthsList->first(function ($month) {
    //             return $month['value'] === date('m') && $month['year'] === date('Y');
    //         });

    //         $data['calendarMonth'] = $currentMonth['value'] ?? date('m', strtotime($start));
    //         $data['calendarYear'] = $currentMonth['year'] ?? date('Y', strtotime($start));
    //     }

    //     if ($sessionYear) {
    //         $attendanceQuery = Attendance::where([
    //             'student_id' => $request->id,
    //             'class_section_id' => $sessionYear->class_section_id,
    //             'session_year_id' => $sessionYear->id,
    //         ]);

    //         $data['attendance'] = (clone $attendanceQuery)
    //             ->whereMonth('date', $data['calendarMonth'])
    //             ->orderBy('date')
    //             ->get()
    //             ->keyBy(function ($r) {
    //                 return date('Y-m-d', strtotime($r->date));
    //             });

    //         $data['firstHalfAbsentCount'] = (clone $attendanceQuery)
    //             ->where('type', Attendance::TYPE_FIRST_HALF)
    //             ->count();

    //         $data['secondHalfAbsentCount'] = (clone $attendanceQuery)
    //             ->where('type', Attendance::TYPE_SECOND_HALF)
    //             ->count();

    //         $halfDays = $data['firstHalfAbsentCount'] + $data['secondHalfAbsentCount'];

    //         // First/second half absences count as half a present and half an absent day.
    //         $data['presentday'] = (clone $attendanceQuery)
    //             ->where('type', 1)
    //             ->count() + ($halfDays * 0.5);

    //         $data['absentCount'] = (clone $attendanceQuery)
    //             ->where('type', 0)
    //             ->count() + ($halfDays * 0.5);
    //     }

    //     return $request->calendar_only
    //     ? view('students.report.calendar', $data)
    //     : $this->response($request, 'attendance', $data);

    // }
    private function attendanceReport(Request $request, $student, $studentSession, $sessionYear, $data)
    {
        $start = $sessionYear->start_date;
        $end = $sessionYear->end_date;

        $totalDays = Carbon::parse($start)
            ->diffInDays(Carbon::parse($end)) + 1;

        $holidays = Holiday::where('session_year_id', $sessionYear->id)
            ->whereBetween('date', [$start, $end])
            ->count();

        $sunday = 0;
        $currentDate = Carbon::parse($start);

        while ($currentDate <= Carbon::parse($end)) {
            if ($currentDate->isSunday()) {
                $sunday++;
            }
            $currentDate->addDay();
        }

        $data['workingdays'] = $totalDays - ($holidays + $sunday);
        $data['holidayscount'] = $holidays;

        $monthsList = collect($this->generateMonths($start, $end));
        $data['mounth'] = $monthsList;

        if ($request->filled('month')) {

            $data['calendarMonth'] = $request->month;
            $data['calendarYear'] = Carbon::parse($start)->format('Y');

        } else {

            $currentMonth = $monthsList->first(function ($month) {
                return $month['value'] == date('m')
                    && $month['year'] == date('Y');
            });

            $data['calendarMonth'] = $currentMonth['value'] ?? Carbon::parse($start)->format('m');
            $data['calendarYear'] = $currentMonth['year'] ?? Carbon::parse($start)->format('Y');
        }

        // Student must be enrolled in the selected session year
        if ($studentSession) {

            $attendanceQuery = Attendance::where([
                'student_id' => $student->id,
                'class_section_id' => $studentSession->class_section_id,
                'session_year_id' => $sessionYear->id,
            ]);

            $data['attendance'] = (clone $attendanceQuery)
                ->whereMonth('date', $data['calendarMonth'])
                ->orderBy('date')
                ->get()
                ->keyBy(function ($row) {
                    return Carbon::parse($row->date)->format('Y-m-d');
                });

            $data['firstHalfAbsentCount'] = (clone $attendanceQuery)
                ->where('type', Attendance::TYPE_FIRST_HALF)
                ->count();

            $data['secondHalfAbsentCount'] = (clone $attendanceQuery)
                ->where('type', Attendance::TYPE_SECOND_HALF)
                ->count();

            $halfDays = $data['firstHalfAbsentCount'] + $data['secondHalfAbsentCount'];

            $data['presentday'] = (clone $attendanceQuery)
                ->where('type', Attendance::TYPE_PRESENT)
                ->count() + ($halfDays * 0.5);

            $data['absentCount'] = (clone $attendanceQuery)
                ->where('type', Attendance::TYPE_ABSENT)
                ->count() + ($halfDays * 0.5);
        }

        return $request->calendar_only
            ? view('students.report.calendar', $data)
            : $this->response($request, 'attendance', $data);
    }

    private function feesReport(Request $request, $student, $studentSession, $sessionYear, $data)
    {
        $classId = null;
        $classSectionModel = null;
        if ($studentSession) {
            $classSectionModel = ClassSection::find($studentSession->class_section_id);
            $classId = $classSectionModel->class_id ?? null;
        } elseif ($student->class_section) {
            $classSectionModel = $student->class_section;
            $classId = $classSectionModel->class_id;
        }

        $baseAmount = $classId
            ? FeesClass::where([
                'class_id' => $classId,
                'choiceable' => 0,
                'session_year_id' => $sessionYear->id,
            ])->sum('amount')
            : 0;

        $allInstallments = $classId
            ? InstallmentFee::where([
                'session_year_id' => $sessionYear->id,
            ])->orderBy('due_date')->get()
            : collect();

        // installment_fees has no per-installment amount column — split baseAmount equally
        $installmentCount = $allInstallments->count();
        $perInstallmentAmount = $installmentCount > 0 ? round($baseAmount / $installmentCount, 2) : 0;

        $installmentsPaid = collect();
        if ($allInstallments->isNotEmpty()) {
            $installmentsPaid = PaidInstallmentFee::where([
                'student_id' => $student->id,
                'class_id' => $classId,
                'session_year_id' => $sessionYear->id,
                'status' => 1,
            ])->orderBy('date')->get();
        }

        $isInstallmentEnabled = $allInstallments->isNotEmpty() && $installmentsPaid->isNotEmpty();

        // Fetch ALL successful transactions for this student/class/session once (avoids N+1 queries)
        $allTransactions = PaymentTransaction::where([
            'student_id' => $student->id,
            'class_id' => $classId,
            'session_year_id' => $sessionYear->id,
            'payment_status' => 1, // succeeded only
        ])->get();

        // Helper: find the transaction matching a given amount + date + type_of_fee
        $matchTransaction = function ($amount, $date, $typeOfFee) use ($allTransactions) {
            if (! $date) {
                return null;
            }
            $targetDate = \Carbon\Carbon::parse($date)->format('Y-m-d');

            return $allTransactions->first(function ($txn) use ($amount, $targetDate, $typeOfFee) {
                return $txn->type_of_fee == $typeOfFee
                    && (float) $txn->total_amount === (float) $amount
                    && $txn->date
                    && \Carbon\Carbon::parse($txn->date)->format('Y-m-d') === $targetDate;
            });
        };

        $row = new \stdClass;
        $row->total_amount = $baseAmount;
        $row->date = null;
        $row->mode = null;
        $row->status_label = 'pending';

        $data['isInstallmentEnabled'] = $isInstallmentEnabled;
        $data['classSection'] = $classSectionModel;
        $data['ledgerRows'] = collect();
        $data['paymentHistory'] = collect();
        $data['courseFees'] = $baseAmount;
        $data['paidAmount'] = 0;
        $data['outstandingAmount'] = $baseAmount;

        if ($isInstallmentEnabled) {

            $data['ledgerRows'] = $allInstallments->map(function ($installment) use ($installmentsPaid, $matchTransaction, $perInstallmentAmount) {
                $paidRecord = $installmentsPaid->firstWhere('installment_fee_id', $installment->id);
                $dueDate = \Carbon\Carbon::parse($installment->due_date);

                // Paid installments always show the actual amount that was paid.
                // Unpaid installments show the calculated equal share of the total course fee.
                $amount = $paidRecord->amount ?? $perInstallmentAmount;
                $dueCharges = $paidRecord->due_charges ?? $installment->due_charges ?? 0;

                $txn = $paidRecord ? $matchTransaction($amount, $paidRecord->date, 1) : null;

                return (object) [
                    'id' => $installment->id,
                    'amount' => $amount,
                    'due_date' => $dueDate->format('d-m-Y'),
                    'due_charges' => $dueCharges,
                    'is_paid' => (bool) $paidRecord,
                    'paid_date' => $paidRecord ? \Carbon\Carbon::parse($paidRecord->date)->format('d-m-Y') : null,
                    'is_overdue' => ! $paidRecord && $dueDate->lt(now()),
                    'mode' => $txn->mode ?? null,
                    'transaction_id' => $txn->payment_id ?? null,
                    'cheque_number' => $txn->cheque_no ?? null,
                ];
            });

            $paidCount = $data['ledgerRows']->where('is_paid', true)->count();
            $totalPaid = $installmentsPaid->sum('amount');
            $totalInstallments = $allInstallments->count();

            $data['courseFees'] = $baseAmount;
            $data['paidAmount'] = $totalPaid;
            $data['outstandingAmount'] = max($baseAmount - $totalPaid, 0);

            $row->date = $installmentsPaid->last()->date;

            $latestTxn = $allTransactions->where('type_of_fee', 1)->sortByDesc('date')->first();
            $row->mode = $latestTxn->mode ?? null;

            $row->status_label = ($totalInstallments > 0 && $paidCount >= $totalInstallments)
                ? 'paid'
                : 'partial';
        } else {

            $feesPaidHistory = FeesPaid::where([
                'student_id' => $request->id,
                'session_year_id' => $sessionYear->id,
            ])->orderBy('date', 'desc')->get();

            $data['paymentHistory'] = $feesPaidHistory->map(function ($payment) use ($matchTransaction) {
                $txn = $matchTransaction($payment->total_amount, $payment->date, 0);

                $payment->mode = $txn->mode ?? null;
                $payment->transaction_id = $txn->payment_id ?? null;
                $payment->cheque_number = $txn->cheque_no ?? null;

                return $payment;
            });

            $feesPaid = $feesPaidHistory->first();

            if ($feesPaid) {
                $row->date = $feesPaid->date;
                $row->status_label = $feesPaid->is_fully_paid ? 'paid' : 'partial';

                $latestTxn = $allTransactions->sortByDesc('date')->first();
                $row->mode = $latestTxn->mode ?? null;

                $data['courseFees'] = $baseAmount;
                $data['paidAmount'] = $feesPaid->total_amount;
                $data['outstandingAmount'] = max($baseAmount - $feesPaid->total_amount, 0);
            }
        }

        $data['feesDetail'] = collect([$row]);

        return $this->response($request, 'fees', $data);

    }

    private function leaveReport(
        Request $request,
        $student,
        $sessionYear,
        $data
    ) {
        $data['leaveDetail'] = Leave::where([
            'user_id' => $student->user_id,
            'session_year_id' => $sessionYear->id,
        ])->get();

        return $this->response($request, 'leave', $data);
    }

    private function assignmentReport(Request $request, $student, $sessionYear, $data)
    {

        $semesterId = $request->get('semester_id');

        $query = AssignmentSubmission::with([
            'assignment.subject',
            'assignment.session_year',
            'assignment.semester',
            'assignment.class_section.subject_teachers.teacher.user',
            'file',
        ])
            ->where('student_id', $request->id)
            ->where('session_year_id', $sessionYear->id);

        // Semester Filter
        if (! empty($semesterId) && $semesterId != 'all') {

            $query->whereHas('assignment', function ($q) use ($semesterId) {
                $q->where('semester_id', $semesterId);
            });
        }

        $data['Assignment'] = $query->get();

        $data['semesters'] = Semester::where(
            'session_year_id',
            $sessionYear->id
        )->get();

        return $this->response($request, 'assignment', $data);
    }

    private function examReport(Request $request, $student, $sessionYear, $data)
    {

        $data['examDetail'] = ExamMarks::with(['subject', 'timetable.exam'])
            ->where(['student_id' => $request->id, 'session_year_id' => $sessionYear->id])
            ->get();

        // Online exams this student attempted, scoped to the session year
        $onlineExamIds = OnlineExamStudentAnswer::where('student_id', $request->id)
            ->distinct()
            ->pluck('online_exam_id');

        $onlineExams = OnlineExam::with(['subject'])
            ->whereIn('id', $onlineExamIds)
            ->where('session_year_id', $sessionYear->id)
            ->get();

        $onlineExamData = [];

        foreach ($onlineExams as $onlineExam) {

            // total marks possible for this exam
            $totalMarks = OnlineExamQuestionChoice::where('online_exam_id', $onlineExam->id)->sum('marks');

            // a question counts only when exactly its correct options were picked
            $score = app(\App\Services\OnlineExamScoringService::class)->score((int) $request->id, (int) $onlineExam->id);

            $obtainedMarks = OnlineExamQuestionChoice::where('online_exam_id', $onlineExam->id)
                ->whereIn('id', $score['correct_choice_ids'])
                ->sum('marks');

            $onlineExamData[] = [
                'online_exam' => $onlineExam,
                'total_marks' => $totalMarks,
                'obtained_marks' => $obtainedMarks,
            ];
        }

        $data['onlineExamDetail'] = $onlineExamData;

         $data['gradeRanges'] = Grade::where('session_year_id', $sessionYear->id)->get();
       

        return $this->response($request, 'exam', $data);
    }

    private function response($request, $tab, $data)
    {
        if ($request->ajax()) {
            return view("students.report.$tab", $data);
        }

        return view('students.report.index', $data + compact('tab'));
    }

    private function generateMonths($start, $end)
    {
        $months = [];

        $date = new \DateTime($start);
        $endDate = new \DateTime($end);

        while ($date <= $endDate) {

            $months[] = [
                'value' => $date->format('m'),
                'year' => $date->format('Y'),
                'name' => $date->format('F Y'),
            ];

            $date->modify('+1 month');
        }

        return $months;
    }
}
