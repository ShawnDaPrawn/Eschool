<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\RequirePasswordChange;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\Event;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\Parents;
use App\Models\SessionYear;
use App\Models\Stream;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\Timetable;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Money\FeeReceiptOverview;
use App\Services\Panel\SetupChecklist;
use App\Services\Panel\TodayNumbers;
use App\Services\Platform\Files\FileNames;
use App\Services\Platform\Identity\AppTokens;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function login(): RedirectResponse|View
    {
        if (Auth::user()) {
            return redirect('home');
        }

        return view('auth.login');
    }

    public function resetpassword(): View
    {
        return view('settings.reset_password');
    }

    public function checkPassword(Request $request): JsonResponse
    {
        $old_password = $request->input('old_password');
        $password = User::where('id', Auth::id())->first();

        $isValid = $password && Hash::check($old_password, $password->password);

        return response()->json($isValid ? 1 : 0);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => ['required', Password::defaults()],
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = $request->user();
        if (! $user || ! Hash::check((string) $request->input('old_password'), $user->password)) {
            return response()->json([
                'error' => true,
                'message' => trans('current_password_incorrect'),
            ]);
        }
        if (RequirePasswordChange::isDefaultPassword($user, (string) $request->input('new_password'))) {
            return response()->json([
                'error' => true,
                'message' => trans('Choose a password other than the default one.'),
            ]);
        }

        try {
            // Saved on the signed-in user so this session keeps working; other
            // sessions and the apps are signed out (AuthenticateSession, tokens).
            $user->password = Hash::make($request->input('new_password'));
            $user->save();
            AppTokens::revokeAll($user);
            $request->session()->forget(RequirePasswordChange::SESSION_KEY);

            return response()->json([
                'error' => false,
                'message' => trans('data_update_successfully'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function index()
    // : View
    {
        $session_year = (int) (session('session_year') ?: (getSettings('session_year')['session_year'] ?? 0));

        $user = Auth::user();

        $data = [
            'session_year_id' => $session_year,
            'admin_name' => $user->full_name,
            'is_super_admin' => $user->hasRole('Super Admin'),
            'is_teacher' => $user->hasRole('Teacher'),
            'is_staff' => ! $user->hasRole('Super Admin') && ! $user->hasRole('Teacher'),
        ];

        // Super Admin and Teacher have dedicated dashboards; every other (staff) role gets a
        // permission-gated subset of the Super Admin widgets.
        if ($user->hasRole('Super Admin')) {
            $data = array_merge($data, $this->getDashboardInitialData($session_year));
        } elseif ($user->hasRole('Teacher')) {
            $data = array_merge($data, $this->getTeacherDashboardInitialData($session_year));
        } else {
            $data = array_merge($data, $this->getStaffDashboardInitialData($session_year, $user));
        }

        // The card and its endpoint use the same fresh financial authority.
        $data['can_read_fee_receipts'] = false;
        try {
            app(MoneyPolicy::class)->authorize($user, 'fee-payments-manage');
            $data['can_read_fee_receipts'] = true;
        } catch (HttpExceptionInterface $denied) {
            if ($denied->getStatusCode() !== 403) {
                throw $denied;
            }
        }

        // Setup checklist (people who can complete a step) and today's numbers.
        $data['checklist'] = app(SetupChecklist::class)->forUser($user);
        $data['today'] = $user->hasRole('Teacher') ? [] : app(TodayNumbers::class)->forUser($user, $session_year);

        return view('home', $data);
    }

    /**
     * Server-rendered (cheap) data for the Super Admin dashboard first paint.
     * The heavier/filterable widgets hydrate via their own AJAX endpoints.
     *
     * @return array<string, mixed>
     */
    private function getDashboardInitialData(int $session_year_id): array
    {
        return [
            'currency_symbol' => getSettings('currency_symbol')['currency_symbol'] ?? '',
            'counts' => $this->getDashboardCounts($session_year_id),
            'total_students_chart' => $this->getStudentGenderCounts(),
            'classes' => ClassSchool::with('streams', 'medium')->orderBy('name')->get(),
            'assessment_options' => $this->getAssessmentOptions(),
            'notices' => $this->getDashboardNotices($session_year_id),
            'pending_leaves_count' => $this->getPendingLeavesCount($session_year_id),
            'months' => $this->getSessionMonths($session_year_id),
            'default_month' => $this->getDefaultMonth($session_year_id),
            // The Super Admin always sees the "view pending leaves" link in the leaves widget.
            'can_approve_leaves' => true,
        ];
    }

    /**
     * Permission-gated data for the staff (Admin / custom role) dashboard first paint.
     * Only the widgets the user is permitted to see are hydrated; Total Students, Upcoming
     * Events and the Noticeboard are visible to every staff member.
     *
     * @return array<string, mixed>
     */
    private function getStaffDashboardInitialData(int $session_year_id, User $user): array
    {
        $data = [
            // Visible to every staff member.
            'counts' => $this->getDashboardCounts($session_year_id),
            'total_students_chart' => $this->getStudentGenderCounts(),
            'notices' => $this->getDashboardNotices($session_year_id),
            'months' => $this->getSessionMonths($session_year_id),
            'default_month' => $this->getDefaultMonth($session_year_id),
            'currency_symbol' => '',
            'can_approve_leaves' => $user->can('leave-approve'),
        ];

        if ($user->can('fee-payments-manage')) {
            $data['currency_symbol'] = getSettings('currency_symbol')['currency_symbol'] ?? '';
        }

        if ($user->can('attendance-report')) {
            $data['classes'] = ClassSchool::with('streams', 'medium')->orderBy('name')->get();
        }

        if ($user->can('view-exam-result')) {
            $data['assessment_options'] = $this->getAssessmentOptions();
        }

        if ($user->can('leave-list')) {
            $data['pending_leaves_count'] = $this->getPendingLeavesCount($session_year_id);
        }

        return $data;
    }

    /**
     * @return array<string, int>
     */
    private function getDashboardCounts(int $session_year_id): array
    {
        $session_students = StudentSessions::where('status', 1)
            ->where('session_year_id', $session_year_id)
            ->distinct('student_id')
            ->count('student_id');

        return [
            'total_students' => Students::count(),
            'session_students' => $session_students,
            'total_teachers' => Teacher::count(),
            'total_classes' => ClassSchool::count(),
            'total_streams' => Stream::count(),
        ];
    }

    /**
     * Boys/Girls counts across all (non-deleted) students, for the Total Students donut.
     *
     * @return array<string, int>
     */
    private function getStudentGenderCounts(): array
    {
        $boys = User::whereHas('student')->where('gender', 'male')->count();
        $girls = User::whereHas('student')->where('gender', 'female')->count();

        return [
            'total' => Students::count(),
            'boys' => $boys,
            'girls' => $girls,
        ];
    }

    /**
     * Class-section options for the assessment filter.
     *
     * @return array<int, array{class_section_id:int, label:string}>
     */
    private function getAssessmentOptions(): array
    {
        return ClassSection::with('class.medium', 'section')
            ->get()
            ->filter(fn ($cs) => $cs->class && $cs->section)
            ->map(fn ($cs) => [
                'class_section_id' => $cs->id,
                'label' => trim($cs->class->name.' - '.$cs->section->name.' '.($cs->class->medium->name ?? '')),
            ])
            ->values()
            ->all();
    }

    /**
     * Latest notices (any scope) with a class chip + thumbnail for the noticeboard.
     */
    private function getDashboardNotices(int $session_year_id, int $limit = 5): Collection
    {
        return Announcement::where('session_year_id', $session_year_id)
            ->with('file')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($notice) {
                $file = $notice->file->first();

                return [
                    'title' => $notice->title,
                    'description' => $notice->description,
                    'date' => $notice->created_at,
                    'class_label' => $this->announcementClassLabel($notice),
                    'thumb' => $file ? ($file->file_thumbnail ?: null) : null,
                ];
            });
    }

    private function announcementClassLabel(Announcement $notice): ?string
    {
        if (empty($notice->table_type) || empty($notice->table_id)) {
            return null;
        }

        $table = $notice->table;
        if (! $table) {
            return null;
        }

        if ($notice->table_type === ClassSection::class) {
            $table->loadMissing('class', 'section');

            return trim(($table->class->name ?? '').($table->section->name ?? ''));
        }

        if ($notice->table_type === ClassSchool::class) {
            return $table->name ?? null;
        }

        if ($notice->table_type === SubjectTeacher::class) {
            $table->loadMissing('class_section', 'subject');
            $classSection = $table->class_section;
            $label = $classSection
                ? trim(($classSection->class->name ?? '').($classSection->section->name ?? ''))
                : '';

            if ($table->subject) {
                $label = trim($label.' - '.$table->subject->name);
            }

            return $label !== '' ? $label : null;
        }

        return null;
    }

    private function getPendingLeavesCount(int $session_year_id): int
    {
        return Leave::where('status', 0)
            ->whereHas('leave_master', fn ($q) => $q->where('session_year_id', $session_year_id))
            ->count();
    }

    /**
     * Months within the session year for the events month dropdown.
     *
     * @return array<int, array{value:string, label:string}>
     */
    private function getSessionMonths(int $session_year_id): array
    {
        $sessionYear = SessionYear::find($session_year_id);

        $months = [];

        if ($sessionYear && $sessionYear->start_date && $sessionYear->end_date) {
            $cursor = Carbon::parse($sessionYear->start_date)->startOfMonth();
            $end = Carbon::parse($sessionYear->end_date)->startOfMonth();
            while ($cursor->lessThanOrEqualTo($end)) {
                $months[] = ['value' => $cursor->format('Y-m'), 'label' => $cursor->format('F Y')];
                $cursor->addMonth();
            }
        }

        if (empty($months)) {
            $now = Carbon::now();
            $months[] = ['value' => $now->format('Y-m'), 'label' => $now->format('F Y')];
        }

        return $months;
    }

    private function getDefaultMonth(int $session_year_id): string
    {
        $months = $this->getSessionMonths($session_year_id);
        $current = Carbon::now()->format('Y-m');

        foreach ($months as $month) {
            if ($month['value'] === $current) {
                return $current;
            }
        }

        return $months[0]['value'];
    }

    public function dashboardData(Request $request): JsonResponse
    {
        $session_year = (int) ($request->session_year_id ? $request->session_year_id : (getSettings('session_year')['session_year'] ?? 0));
        $user = Auth::user();

        // Initialize base data structure
        $data = [
            'counts' => [
                'teacher' => null,
                'student' => null,
                'parent' => null,
            ],
            'class_sections' => null,
            'teachers' => null,
            'rankers' => null,
            'attendance' => null,
            'leaves' => null,
            'announcement' => null,
            'boys' => 0,
            'girls' => 0,
        ];

        // Handle Super Admin specific data
        if ($user->hasRole('Super Admin')) {
            $superAdminData = $this->getSuperAdminData($session_year);

            $data['counts'] = [
                'teacher' => $superAdminData['teacher'],
                'student' => $superAdminData['student'],
                'parent' => $superAdminData['parent'],
            ];
            $data['teachers'] = $superAdminData['teachers'];
            $data['boys'] = $superAdminData['boys'];
            $data['girls'] = $superAdminData['girls'];
            $data['rankers'] = $superAdminData['rankers'];
        }

        // Handle Teacher specific data
        if ($user->hasRole('Teacher')) {
            $data['class_sections'] = $this->getTeacherClassSections($user->teacher->id, $session_year);

            // For teachers, we don't need counts, teachers list, or rankers
            unset($data['counts']);
            unset($data['teachers']);
            unset($data['rankers']);
        }

        // Common data for all users
        $data['announcement'] = Announcement::where('table_type', '')
            ->where('session_year_id', $session_year)
            ->latest()
            ->limit(3)
            ->get();

        $data['attendance'] = Attendance::with('class_section')
            ->where('session_year_id', $session_year)
            ->select(
                'class_section_id',
                'type',
                'date',
                DB::raw('COUNT(*) as total_attendance'),
                DB::raw('SUM(CASE WHEN type = 1 THEN 1 WHEN type IN (4, 5) THEN 0.5 ELSE 0 END) as total_present')
            )
            ->groupBy('class_section_id')
            ->get();

        $data['leaves'] = $this->getUpcomingLeaves((int) $session_year);

        return response()->json($data);
    }

    /**
     * Fees Collection widget (donut + collected / upcoming / overdue).
     */
    public function feesCollectionWidget(Request $request, FeeReceiptOverview $receipts): JsonResponse
    {
        $request->validate(['session_year_id' => ['sometimes', 'integer', 'min:1']]);
        $year = $request->integer('session_year_id') ?: (int) schoolSetting('session_year');

        return response()->json($receipts->read($request->user(), $year))->header('Cache-Control', 'no-store, private');
    }

    /**
     * Attendance widget: present/absent percentage per section of the selected class.
     */
    public function attendanceWidget(Request $request): JsonResponse
    {
        ResponseService::noPermissionThenSendJson('attendance-report');

        $session_year_id = (int) ($request->session_year_id ?: (getSettings('session_year')['session_year'] ?? 0));
        $class_id = (int) $request->class_id;

        $class = ClassSchool::find($class_id);
        $sections = ClassSection::with('section')->where('class_id', $class_id)->get();

        // A teacher (not Super Admin) only sees the sections of which they are Class Teacher.
        $user = Auth::user();
        if ($user->hasRole('Teacher') && ! $user->hasRole('Super Admin') && $user->teacher) {
            $allowedSectionIds = ClassTeacher::where('class_teacher_id', $user->teacher->id)
                ->where('session_year_id', $session_year_id)
                ->pluck('class_section_id');
            $sections = $sections->whereIn('id', $allowedSectionIds)->values();
        }

        $aggregates = Attendance::where('session_year_id', $session_year_id)
            ->whereIn('class_section_id', $sections->pluck('id'))
            ->whereIn('type', [0, 1, 4, 5])
            ->selectRaw('class_section_id,
                SUM(CASE WHEN type = 1 THEN 1 WHEN type IN (4, 5) THEN 0.5 ELSE 0 END) as present_count,
                SUM(CASE WHEN type = 0 THEN 1 WHEN type IN (4, 5) THEN 0.5 ELSE 0 END) as absent_count')
            ->groupBy('class_section_id')
            ->get()
            ->keyBy('class_section_id');

        $data = $sections->map(function ($section) use ($aggregates) {
            $row = $aggregates->get($section->id);
            $present = (float) ($row->present_count ?? 0);
            $absent = (float) ($row->absent_count ?? 0);
            $total = $present + $absent;
            $presentPct = $total > 0 ? round(($present * 100) / $total, 2) : 0;

            return [
                'section_id' => $section->id,
                'label' => $section->section->name ?? '',
                'present_pct' => $presentPct,
                'absent_pct' => $total > 0 ? round(100 - $presentPct, 2) : 0,
            ];
        })->values();

        return response()->json([
            'class_name' => $class->name ?? '',
            'sections' => $data,
        ]);
    }

    /**
     * Recent Assessment Performance widget: top 5 students for a class-section
     * based on the most recent OFFLINE exam result (exam_results.percentage).
     */
    public function assessmentWidget(Request $request): JsonResponse
    {
        ResponseService::noPermissionThenSendJson('view-exam-result');

        $session_year_id = (int) ($request->session_year_id ?: (getSettings('session_year')['session_year'] ?? 0));
        $class_section_id = (int) $request->class_section_id;

        $classSection = ClassSection::with('class.medium', 'section')->find($class_section_id);

        $students = collect();

        if ($classSection) {
            // Most recent offline exam (that has results for this section) by its timetable date.
            $latest_exam_id = ExamTimetable::where('class_id', $classSection->class_id)
                ->where('session_year_id', $session_year_id)
                ->whereHas('exam')
                ->whereIn('exam_id', ExamResult::where('class_section_id', $class_section_id)
                    ->where('session_year_id', $session_year_id)
                    ->select('exam_id'))
                ->orderByDesc('date')
                ->value('exam_id');

            if ($latest_exam_id) {
                $students = ExamResult::with('student.user')
                    ->where('class_section_id', $class_section_id)
                    ->where('session_year_id', $session_year_id)
                    ->where('exam_id', $latest_exam_id)
                    ->orderByDesc('percentage')
                    ->take(5)
                    ->get()
                    ->map(function ($result) {
                        $user = optional($result->student)->user;

                        return [
                            'student_id' => $result->student_id,
                            'name' => $user->full_name ?? '',
                            'image' => $user->image ?? '',
                            'percentage' => round((float) $result->percentage, 2),
                        ];
                    });
            }
        }

        return response()->json([
            'class_label' => $classSection
                ? trim(($classSection->class->name ?? '').' - '.($classSection->section->name ?? '').' '.($classSection->class->medium->name ?? ''))
                : '',
            'students' => $students,
        ]);
    }

    /**
     * Approved Leaves widget for a relative day filter (today | tomorrow | upcoming).
     */
    public function leavesWidget(Request $request): JsonResponse
    {
        ResponseService::noPermissionThenSendJson('leave-list');

        $session_year_id = (int) ($request->session_year_id ?: (getSettings('session_year')['session_year'] ?? 0));
        $filter = $request->filter ?: 'today';
        $today = Carbon::now();

        $query = LeaveDetail::whereHas('leave', function ($q) use ($session_year_id) {
            $q->where('status', 1)
                ->whereHas('leave_master', fn ($m) => $m->where('session_year_id', $session_year_id));
        })->with('leave.user');

        if ($filter === 'tomorrow') {
            $query->whereDate('date', $today->copy()->addDay()->format('Y-m-d'));
        } elseif ($filter === 'upcoming') {
            $query->whereDate('date', '>=', $today->format('Y-m-d'));
        } else {
            $query->whereDate('date', $today->format('Y-m-d'));
        }

        $rows = $query->orderBy('date')->take(15)->get()->map(function ($detail) {
            $user = optional($detail->leave)->user;

            return [
                'name' => $user->full_name ?? '',
                'image' => $user->image ?? '',
                'date' => date('d-m-Y', strtotime($detail->date)),
                'type' => $detail->type,
                'status' => 'approved',
            ];
        });

        return response()->json([
            'pending_count' => $this->getPendingLeavesCount($session_year_id),
            'rows' => $rows->values(),
        ]);
    }

    /**
     * Upcoming Events widget: event list + month calendar grid for the selected month.
     */
    public function eventsWidget(Request $request): JsonResponse
    {
        $session_year_id = (int) ($request->session_year_id ?: (getSettings('session_year')['session_year'] ?? 0));
        $month = $request->month ?: Carbon::now()->format('Y-m');

        [$year, $monthNumber] = array_map('intval', array_pad(explode('-', $month), 2, Carbon::now()->month));
        $start = Carbon::createFromDate($year, $monthNumber, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $events = Event::where('session_year_id', $session_year_id)
            ->whereBetween('start_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($event) {
                $time = '';
                if ($event->start_time) {
                    $time = date('h:i A', strtotime($event->start_time));
                    if ($event->end_time) {
                        $time .= ' - '.date('h:i A', strtotime($event->end_time));
                    }
                }

                return [
                    'title' => $event->title,
                    'image' => $event->image,
                    'date' => date('d-m-Y', strtotime($event->start_date)),
                    'time' => $time,
                    'day' => (int) date('j', strtotime($event->start_date)),
                ];
            });

        $holidays = Holiday::where('session_year_id', $session_year_id)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        $today = Carbon::now();

        return response()->json([
            'month_label' => $start->format('F Y'),
            'events' => $events->values(),
            'calendar' => [
                'year' => $year,
                'month' => $monthNumber,
                'month_label' => $start->format('F Y'),
                'first_weekday' => (int) $start->dayOfWeekIso, // 1 = Monday ... 7 = Sunday
                'days_in_month' => $start->daysInMonth,
                'today' => ($today->year === $year && $today->month === $monthNumber) ? $today->day : null,
                'event_days' => $events->pluck('day')->unique()->values(),
                'holiday_days' => $holidays->map(fn ($holiday) => (int) date('j', strtotime($holiday->date)))->unique()->values(),
            ],
        ]);
    }

    /* =========================================================================
     |  Teacher dashboard
     | ========================================================================= */

    /**
     * Server-rendered data for the Teacher dashboard first paint.
     *
     * @return array<string, mixed>
     */
    private function getTeacherDashboardInitialData(int $session_year_id): array
    {
        $teacher = Auth::user()->teacher;

        if (! $teacher) {
            return ['counts' => ['total_students' => 0, 'total_classes' => 0, 'total_subjects' => 0]];
        }

        // Class-sections where the teacher is the Class Teacher.
        $classTeacherSectionIds = ClassTeacher::where('class_teacher_id', $teacher->id)
            ->where('session_year_id', $session_year_id)
            ->pluck('class_section_id')->unique();

        // Class-sections where the teacher teaches a subject (Subject Teacher).
        $subjectTeacherSectionIds = SubjectTeacher::where('teacher_id', $teacher->id)
            ->where('session_year_id', $session_year_id)
            ->pluck('class_section_id')->unique();

        $allSectionIds = $classTeacherSectionIds;

        $totalStudents = StudentSessions::whereIn('class_section_id', $allSectionIds)
            ->where('session_year_id', $session_year_id)
            ->where('status', 1)
            ->distinct('student_id')->count('student_id');

        $totalSubjects = SubjectTeacher::where('teacher_id', $teacher->id)
            ->where('session_year_id', $session_year_id)->count('subject_id');

        // Attendance dropdown: classes the teacher is a Class Teacher of (access rule).
        $classTeacherClassIds = ClassSection::whereIn('id', $classTeacherSectionIds)->pluck('class_id')->unique();
        $attendanceClasses = ClassSchool::with('streams', 'medium')
            ->whereIn('id', $classTeacherClassIds)
            ->orderBy('name')->get();

        // Assignment dropdown: class-sections where the teacher is a Subject Teacher.
        $assignmentOptions = ClassSection::with('class', 'section')
            ->whereIn('id', $subjectTeacherSectionIds)
            ->get()
            ->filter(fn ($cs) => $cs->class && $cs->section)
            ->map(fn ($cs) => [
                'class_section_id' => $cs->id,
                'label' => trim($cs->class->name.'-'.$cs->section->name),
            ])->values()->all();

        return [
            'counts' => [
                'total_students' => $totalStudents,
                'total_classes' => $allSectionIds->count(),
                'total_subjects' => $totalSubjects,
            ],
            'classes' => $attendanceClasses,
            'assignment_options' => $assignmentOptions,
            'timetable' => $this->getTeacherTodayTimetable($teacher->id, $session_year_id),
            'notices' => $this->getTeacherNotices($teacher->id, $session_year_id),
            'months' => $this->getSessionMonths($session_year_id),
            'default_month' => $this->getDefaultMonth($session_year_id),
        ];
    }

    /**
     * The teacher's own teaching schedule for today (weekly recurring timetable).
     */
    private function getTeacherTodayTimetable(int $teacher_id, int $session_year_id): Collection
    {
        $currentDay = Carbon::now()->format('l');

        $subjectTeacherIds = SubjectTeacher::where('teacher_id', $teacher_id)
            ->where('session_year_id', $session_year_id)
            ->pluck('id');

        return Timetable::whereIn('subject_teacher_id', $subjectTeacherIds)
            ->where('session_year_id', $session_year_id)
            ->where('day_name', $currentDay)
            ->with('class_section.class', 'class_section.section', 'subject.subject')
            ->orderBy('start_time')
            ->get()
            ->map(function ($row) {
                $cs = $row->class_section;

                return [
                    'time' => date('h:i A', strtotime($row->start_time)).' - '.date('h:i A', strtotime($row->end_time)),
                    'class_label' => $cs ? trim(($cs->class->name ?? '').($cs->section->name ?? '')) : '',
                    'subject' => optional(optional($row->subject)->subject)->name ?? '',
                ];
            });
    }

    /**
     * Latest notices scoped to the subjects the teacher teaches (Subject Teacher rule).
     */
    private function getTeacherNotices(int $teacher_id, int $session_year_id, int $limit = 5): Collection
    {
        $subjectTeacherIds = SubjectTeacher::where('teacher_id', $teacher_id)
            ->where('session_year_id', $session_year_id)
            ->pluck('id');

        return Announcement::where('table_type', SubjectTeacher::class)
            ->whereIn('table_id', $subjectTeacherIds)
            ->with('file')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($notice) {
                $file = $notice->file->first();
                $st = $notice->table;
                if ($st) {
                    $st->loadMissing('class_section.class', 'class_section.section', 'subject');
                }

                return [
                    'title' => $notice->title,
                    'description' => $notice->description,
                    'date' => $notice->created_at,
                    'class_label' => $this->subjectTeacherNoticeLabel($st),
                    'thumb' => $file ? ($file->file_thumbnail ?: null) : null,
                ];
            });
    }

    private function subjectTeacherNoticeLabel(?SubjectTeacher $st): ?string
    {
        if (! $st) {
            return null;
        }

        $cs = $st->class_section;
        $classLabel = $cs ? trim(($cs->class->name ?? '').($cs->section->name ?? '')) : '';
        $subject = optional($st->subject)->name;

        return $subject ? trim($classLabel.' - '.$subject) : ($classLabel ?: null);
    }

    /**
     * Teacher Leaves widget: pending student leave requests for the teacher's
     * Class Teacher classes, filtered by relative day (today | tomorrow | upcoming).
     */
    public function teacherLeavesWidget(Request $request): JsonResponse
    {
        $session_year_id = (int) ($request->session_year_id ?: (getSettings('session_year')['session_year'] ?? 0));
        $teacher = Auth::user()->teacher;
        $filter = $request->filter ?: 'today';
        $today = Carbon::now();

        if (! $teacher) {
            return response()->json(['pending_count' => 0, 'rows' => []]);
        }

        $classSectionIds = ClassTeacher::where('class_teacher_id', $teacher->id)
            ->where('session_year_id', $session_year_id)
            ->pluck('class_section_id');

        $studentUserIds = Students::whereHas('studentSessions', function ($q) use ($classSectionIds, $session_year_id) {
            $q->whereIn('class_section_id', $classSectionIds)->where('session_year_id', $session_year_id);
        })->pluck('user_id');

        $query = LeaveDetail::whereHas('leave', function ($q) use ($session_year_id, $studentUserIds) {
            $q->where('status', 0)
                ->where('session_year_id', $session_year_id)
                ->whereIn('user_id', $studentUserIds);
        })->with('leave.user');

        if ($filter === 'tomorrow') {
            $query->whereDate('date', $today->copy()->addDay()->format('Y-m-d'));
        } elseif ($filter === 'upcoming') {
            $query->whereDate('date', '>=', $today->format('Y-m-d'));
        } else {
            $query->whereDate('date', $today->format('Y-m-d'));
        }

        $rows = $query->orderBy('date')->take(20)->get()->map(function ($detail) {
            $user = optional($detail->leave)->user;

            return [
                'name' => $user->full_name ?? '',
                'image' => $user->image ?? '',
                'date' => date('d-m-Y', strtotime($detail->date)),
                'type' => $detail->type,
                'status' => 'pending',
            ];
        });

        return response()->json([
            'pending_count' => $rows->count(),
            'rows' => $rows->values(),
        ]);
    }

    /**
     * Teacher Upcoming Exams widget: exams in the selected month for the teacher's
     * Class Teacher classes.
     */
    public function teacherExamsWidget(Request $request): JsonResponse
    {
        $session_year_id = (int) ($request->session_year_id ?: (getSettings('session_year')['session_year'] ?? 0));
        $teacher = Auth::user()->teacher;
        $month = $request->month ?: Carbon::now()->format('Y-m');

        [$year, $monthNumber] = array_map('intval', array_pad(explode('-', $month), 2, Carbon::now()->month));
        $start = Carbon::createFromDate($year, $monthNumber, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $exams = collect();

        if ($teacher) {
            $classSectionIds = ClassTeacher::where('class_teacher_id', $teacher->id)
                ->where('session_year_id', $session_year_id)
                ->pluck('class_section_id');
            $classIds = ClassSection::whereIn('id', $classSectionIds)->pluck('class_id')->unique();

            $exams = ExamTimetable::where('session_year_id', $session_year_id)
                ->whereIn('class_id', $classIds)
                ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->with('exam:id,name', 'subject:id,name', 'class:id,name')
                ->orderBy('date')
                ->get()
                ->groupBy(fn ($t) => $t->exam_id.'-'.$t->class_id)
                ->map(function ($rows) {
                    $first = $rows->first();
                    $date = $rows->min('date');

                    return [
                        'name' => optional($first->exam)->name ?? '',
                        'day' => date('d', strtotime($date)),
                        'month' => strtoupper(date('M', strtotime($date))),
                        'year' => date('Y', strtotime($date)),
                        'class_label' => optional($first->class)->name ?? '',
                        'subjects' => $rows->map(fn ($r) => optional($r->subject)->name)->filter()->unique()->implode(' + '),
                    ];
                })->values();
        }

        return response()->json([
            'month_label' => $start->format('F Y'),
            'exams' => $exams,
        ]);
    }

    /**
     * Teacher Assignments widget: recent assignments for the teacher's subjects,
     * each with submitted-vs-total counts. Optionally filtered by class-section.
     */
    public function teacherAssignmentsWidget(Request $request): JsonResponse
    {
        $session_year_id = (int) ($request->session_year_id ?: (getSettings('session_year')['session_year'] ?? 0));
        $class_section_id = $request->class_section_id ? (int) $request->class_section_id : null;

        $assignments = Assignment::assignmentTeachers()
            ->where('session_year_id', $session_year_id)
            ->when($class_section_id, fn ($q) => $q->where('class_section_id', $class_section_id))
            ->with('subject:id,name')
            ->orderByDesc('due_date')
            ->take(8)
            ->get();

        $rows = $assignments->map(function ($assignment) {
            $submitted = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->distinct('student_id')->count('student_id');

            $total = StudentSessions::where('class_section_id', $assignment->class_section_id)
                ->where('session_year_id', $assignment->session_year_id)
                ->where('status', 1)
                ->distinct('student_id')->count('student_id');

            return [
                'name' => $assignment->name,
                'subject' => optional($assignment->subject)->name ?? '',
                'due_date' => date('d-m-Y', strtotime($assignment->due_date)),
                'submitted' => $submitted,
                'total' => $total,
            ];
        });

        return response()->json(['assignments' => $rows->values()]);
    }

    // private function getSuperAdminData($session_year_id): array
    // {
    //     $teacher = Teacher::count();
    //     $studentSessions = StudentSessions::with('student')
    //         ->where('status', 1)
    //         ->where('session_year_id', $session_year_id)
    //         ->groupBy('student_id')
    //         ->get();

    //     $student = $studentSessions->count();

    //     $parentIds = $studentSessions
    //         ->flatMap(function ($session) {
    //             if (! $session->student) {
    //                 return [];
    //             }

    //             return [
    //                 $session->student->father_id,
    //                 $session->student->mother_id,
    //                 $session->student->guardian_id,
    //             ];
    //         })
    //         ->filter()
    //         ->unique()
    //         ->values();

    //     $parent = Parents::whereIn('id', $parentIds)->count();

    //     $teachers = Teacher::with('user:id,first_name,last_name,image')->get();

    //     $boys = 0;
    //     $girls = 0;

    //     // Get unique user IDs from the student sessions and count genders from the users table
    //     $studentUserIds = $studentSessions->map(
    //         fn ($s) => optional($s->student)->user_id
    //     )->filter()->unique()->values();

    //     if ($studentUserIds->isNotEmpty()) {
    //         $boys_count = User::whereIn('id', $studentUserIds)->where('gender', 'male')->count();
    //         $girls_count = User::whereIn('id', $studentUserIds)->where('gender', 'female')->count();

    //         $boys = round((($boys_count * 100) / ($boys_count + $girls_count)), 2);
    //         $girls = round((($girls_count * 100) / ($boys_count + $girls_count)), 2);
    //     }

    //     $rankers = ExamResult::with('student.user', 'class_section')
    //         ->where('session_year_id', $session_year_id)
    //         ->select('class_section_id', 'student_id', 'percentage', 'grade', DB::raw('MAX(percentage) as max_percentage'))
    //         ->groupBy('class_section_id')
    //         ->whereNot('grade', 'Fail')
    //         ->get();

    //     return compact('teacher', 'student', 'parent', 'teachers', 'boys', 'girls', 'rankers');
    // }

    private function getTeacherClassSections(int $teacher_id, int $session_year): ?Collection
    {
        $class_section_ids = ClassTeacher::select('class_section_id')
            ->where('class_teacher_id', $teacher_id)
            ->where('session_year_id', $session_year)
            ->pluck('class_section_id');

        if ($class_section_ids->isNotEmpty()) {
            return ClassSection::with('class', 'section', 'class.medium', 'class.streams')
                ->whereIn('id', $class_section_ids)
                ->get();
        }

        return null;
    }

    private function getUpcomingLeaves(int $session_year_id): ?Collection
    {
        $today_date = Carbon::now()->format('Y-m-d');

        return LeaveDetail::whereHas('leave', function ($query) use ($session_year_id) {
            $query->where('status', 1)
                ->whereHas('leave_master', fn ($q) => $q->where('session_year_id', $session_year_id));
        })
            ->with('leave.user')
            ->whereDate('date', '>=', $today_date)
            ->orderBy('date', 'ASC')
            ->take(10)
            ->get();
    }

    public function getSubjectByClassSection(Request $request): JsonResponse
    {
        $session_year_id = (int) ($request->input('session_year_id') ?: (getSettings('session_year')['session_year'] ?? 0));
        $classSectionId = (int) $request->input('class_section_id');
        $classSection = ClassSection::select('class_id')->where('id', $classSectionId)->first();

        if (! $classSection) {
            return response()->json([]);
        }

        $class = ClassSchool::withSessionConfigFor($session_year_id)->find($classSection->class_id);
        if (! $class) {
            return response()->json([]);
        }

        $currentTerm = Term::currentForClass($session_year_id, (int) $class->id);
        $termId = $request->input('term_id');
        $allTerms = $request->boolean('all_terms');

        $subjects = [];
        if ($class->getIncludeTermsForSession($session_year_id)) {
            if ($allTerms) {
                $subjects = ClassSubject::SubjectTeacher($classSectionId)
                    ->where('class_id', $class->id)
                    ->whereNotNull('term_id')
                    ->where('session_year_id', $session_year_id)
                    ->with('subject', 'term')
                    ->get();
            } elseif ($termId || $currentTerm) {
                $effectiveTermId = $termId ?: $currentTerm->id;
                $subjects = ClassSubject::SubjectTeacher($classSectionId, $effectiveTermId)
                    ->where('class_id', $class->id)
                    ->where('term_id', $effectiveTermId)
                    ->where('session_year_id', $session_year_id)
                    ->with('subject')
                    ->get();
            }
        } else {
            $subjects = ClassSubject::SubjectTeacher($classSectionId)
                ->where('class_id', $class->id)
                ->where('session_year_id', $session_year_id)
                ->with('subject')
                ->get();
        }

        return response()->json($subjects);
    }

    // public function getTeacherByClassSubject(Request $request): JsonResponse
    // {
    //     $teachers = Teacher::with('user')->get();

    //     return response()->json($teachers);
    // }
    public function getTeacherByClassSubject(Request $request): JsonResponse
    {
        $classSectionId = $request->class_section_id;
        $subjectId = $request->subject_id;
        $editId = $request->edit_id; // present when editing an existing assignment

        $teachers = Teacher::with('user')
            ->whereHas('user') // exclude teachers with no linked user
            ->when($classSectionId && $subjectId, function ($query) use ($classSectionId, $subjectId, $editId) {
                $query->where(function ($q) use ($classSectionId, $subjectId, $editId) {
                    // Teachers NOT already assigned to this class_section + subject...
                    $q->whereDoesntHave('subjectTeachers', function ($sq) use ($classSectionId, $subjectId) {
                        $sq->where('class_section_id', $classSectionId)
                            ->where('subject_id', $subjectId);
                    });

                    // ...unless it's the one currently being edited (so the current teacher still shows up)
                    if ($editId) {
                        $q->orWhereHas('subjectTeachers', function ($sq) use ($classSectionId, $subjectId, $editId) {
                            $sq->where('id', $editId)
                                ->where('class_section_id', $classSectionId)
                                ->where('subject_id', $subjectId);
                        });
                    }
                });
            })
            ->get();

        return response()->json($teachers);
    }

    public function resetPasswordView(): View
    {
        return view('settings.reset_password');
    }

    public function editProfile(): View
    {
        $admin_data = Auth::user();

        return view('settings.update_profile', compact('admin_data'));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'mobile' => 'required|digits_between:1,16',
            'image' => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'gender' => 'required|in:male,female',
            'dob' => 'required|date',
            'current_address' => 'required',
            'permanent_address' => 'required',
        ]);

        try {
            $user = Auth::user();
            $data = $request->only([
                'first_name',
                'last_name',
                'mobile',
                'gender',
                'current_address',
                'permanent_address',
            ]);

            $data['dob'] = date('Y-m-d', strtotime($request->input('dob')));

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($user->getRawOriginal('image')) {
                    Storage::disk('public')->delete($user->getRawOriginal('image'));
                }

                $file = $request->file('image');
                $fileName = time().'-'.FileNames::forUpload($file);
                $filePath = 'profile/'.$fileName;

                resizeImage($file);

                $destinationPath = storage_path('app/public/profile');
                $file->move($destinationPath, $fileName);

                $data['image'] = $filePath;
            }

            $user->update($data);

            return response()->json([
                'error' => false,
                'message' => trans('data_update_successfully'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function updateWarningModal(Request $request): JsonResponse
    {
        try {
            // Implementation for warning modal update
            return response()->json([
                'error' => false,
                'message' => 'Warning modal updated successfully',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }
}
