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
use App\Models\FormField;
use App\Models\Grade;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestionChoice;
use App\Models\OnlineExamStudentAnswer;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\Term;
use App\Services\OnlineExamScoringService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin panel: a student's report (attendance, leave, assignments, exams).
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
                return redirect()->route('fee-accounts.index');

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
            'examDetail' => collect(),
            'leaveDetail' => collect(),
            'assignment' => collect(),
            'terms' => collect(),
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

        $termId = $request->get('term_id');

        $query = AssignmentSubmission::with([
            'assignment.subject',
            'assignment.session_year',
            'assignment.term',
            'assignment.class_section.subject_teachers.teacher.user',
            'file',
        ])
            ->where('student_id', $request->id)
            ->where('session_year_id', $sessionYear->id);

        // Term Filter
        if (! empty($termId) && $termId != 'all') {

            $query->whereHas('assignment', function ($q) use ($termId) {
                $q->where('term_id', $termId);
            });
        }

        $data['Assignment'] = $query->get();

        $data['terms'] = Term::where(
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
            $score = app(OnlineExamScoringService::class)->score((int) $request->id, (int) $onlineExam->id);

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
