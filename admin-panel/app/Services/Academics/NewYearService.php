<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Actions\Transfers\TransferActivityGroups;
use App\Actions\Transfers\TransferAwardWindows;
use App\Actions\Transfers\TransferBellSchedules;
use App\Actions\Transfers\TransferClassSubject;
use App\Actions\Transfers\TransferClassTeacherAndSubjectTeacher;
use App\Actions\Transfers\TransferClassTimetable;
use App\Actions\Transfers\TransferCurriculumPlans;
use App\Actions\Transfers\TransferDisciplineFramework;
use App\Actions\Transfers\TransferExamGrades;
use App\Actions\Transfers\TransferHouseMemberships;
use App\Actions\Transfers\TransferLeaveSettings;
use App\Actions\Transfers\TransferTeachingGroups;
use App\Models\Academics\BellSchedule;
use App\Models\Academics\CurriculumPlan;
use App\Models\Academics\TeachingGroup;
use App\Models\Activities\ActivityGroup;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\Discipline\DisciplineFramework;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Leave;
use App\Models\LeaveMaster;
use App\Models\People\Enrolment;
use App\Models\Recognition\AwardWindow;
use App\Models\Recognition\HouseMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Models\TermTemplate;
use App\Models\Timetable;
use App\Models\User;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\Notifier;
use App\Services\SessionYearService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * The new-year wizard: create next year, choose what to carry forward, check the
 * year end, preview, then make next year current.
 *
 * Creating the year (SessionYearService::store, with no transfers and without
 * making it current) leaves the current year as it is. The carry-forward choices
 * run only at the switch, through the app/Actions/Transfers actions, and only
 * for what the new year does not have yet, so running the switch twice copies
 * nothing twice. SessionYearService::makeCurrent then applies the recorded
 * promotions and makes the year the one the panel and the apps use.
 */
class NewYearService
{
    /**
     * What the Transfers actions can carry forward: key => [label, action].
     *
     * @var array<string, array{0: string, 1: class-string}>
     */
    public const CARRY = [
        'curriculum' => ['new_year_carry_curriculum', TransferCurriculumPlans::class],
        'subjects' => ['new_year_carry_subjects', TransferClassSubject::class],
        'teachers' => ['new_year_carry_teachers', TransferClassTeacherAndSubjectTeacher::class],
        'timetable' => ['new_year_carry_timetable', TransferClassTimetable::class],
        'grading' => ['new_year_carry_grading', TransferExamGrades::class],
        'leave' => ['new_year_carry_leave', TransferLeaveSettings::class],
        'teaching_groups' => ['new_year_carry_teaching_groups', TransferTeachingGroups::class],
        'activity_groups' => ['new_year_carry_activity_groups', TransferActivityGroups::class],
        'bell_schedules' => ['new_year_carry_bell_schedules', TransferBellSchedules::class],
        'houses' => ['new_year_carry_houses', TransferHouseMemberships::class],
        'award_windows' => ['new_year_carry_award_windows', TransferAwardWindows::class],
        'discipline' => ['new_year_carry_discipline', TransferDisciplineFramework::class],
    ];

    /**
     * Choices written just after the new year becomes current, because their
     * service only writes to the current year.
     */
    public const AFTER_SWITCH = ['discipline'];

    /** The notice families get when the new year becomes current (1.21). */
    public const WELCOME = 'school.new_year';

    /** Choices that exist only while their module is switched on. */
    public const MODULES = ['houses' => 'house_points', 'award_windows' => 'awards', 'discipline' => 'discipline'];

    /**
     * Choices that need another: a lesson belongs to a subject teacher, so
     * carrying the timetable carries the teachers too (the timetable transfer
     * leaves out lessons whose subject teacher the new year does not have).
     *
     * @var array<string, list<string>>
     */
    public const NEEDS = [
        'timetable' => ['teachers'],
    ];

    public function __construct(private readonly SessionYearService $years) {}

    /** The year being prepared: the newest year that starts after the current one and has not ended. */
    public function upcomingYear(SessionYear $current): ?SessionYear
    {
        return SessionYear::query()
            ->where('start_date', '>', $current->start_date)
            ->where('end_date', '>=', now()->toDateString())
            ->whereKeyNot($current->id)
            ->orderByDesc('start_date')
            ->first();
    }

    /**
     * Suggested name, dates, terms and fee settings for the next year: the
     * current year moved on by whole years until it has not ended.
     *
     * @return array{name: string, start_date: string, end_date: string, terms: list<array{name: string, start_date: string, end_date: string}>}
     */
    public function suggestion(SessionYear $current): array
    {
        $shift = 1;
        while (Carbon::parse($current->end_date)->addYears($shift)->isPast()) {
            $shift++;
        }
        $move = fn (mixed $date) => $date ? Carbon::parse((string) $date)->addYears($shift)->toDateString() : '';

        $start = $move($current->start_date);
        $end = $move($current->end_date);
        $terms = $current->terms()->orderBy('start_date')->get()
            ->map(fn ($term) => ['name' => $term->name, 'start_date' => $move($term->start_date), 'end_date' => $move($term->end_date),
                'grade_from' => $term->grade_from, 'grade_to' => $term->grade_to,
                'marks_lock_on' => $move($term->marks_lock_on), 'reports_on' => $move($term->reports_on)])
            ->all();
        if ($terms === []) {
            $terms = array_map(fn ($n) => ['name' => __('new_year_term_name', ['number' => $n]), 'start_date' => '', 'end_date' => ''], range(1, 4));
        }

        return [
            'name' => $this->moveName($current->name, $shift, $start),
            'start_date' => $start,
            'end_date' => $end,
            'terms' => $terms,
        ];
    }

    /**
     * Create next year without touching the current one: no transfers, not current.
     *
     * @param  array{name: string, start_date: string, end_date: string, terms?: list<array{name: string, start_date: string, end_date: string}>}  $data
     */
    public function create(array $data): SessionYear
    {
        $dmy = fn (string $date) => Carbon::parse($date)->format('d-m-Y');
        $terms = array_values(array_filter($data['terms'] ?? [], fn ($term) => ! empty($term['start_date']) && ! empty($term['end_date'])));

        return DB::transaction(function () use ($data, $dmy, $terms) {
            $year = $this->years->store([
                'name' => $data['name'],
                'start_date' => $dmy($data['start_date']),
                'end_date' => $dmy($data['end_date']),
                'set_as_current' => '0',
                'term_data' => empty($data['term_template_id']) ? array_map(fn ($term) => [
                    'name' => $term['name'],
                    'start_date' => $dmy($term['start_date']),
                    'end_date' => $dmy($term['end_date']),
                    'grade_from' => $term['grade_from'] ?? 0, 'grade_to' => $term['grade_to'] ?? 12,
                    'marks_lock_on' => $term['marks_lock_on'] ?? null, 'reports_on' => $term['reports_on'] ?? null,
                ], $terms) : [],
            ]);
            if (! empty($data['term_template_id'])) {
                $template = TermTemplate::query()->findOrFail($data['term_template_id']);
                app(TermCalendar::class)->initialise($year, app(TermTemplates::class)->rows($template, $year));
            }

            return $year;
        });
    }

    /**
     * What each carry-forward choice would copy from one year into the other.
     *
     * @param  list<string>  $chosen
     * @return list<array{key: string, label: string, source: int, existing: int, chosen: bool, copies: bool}>
     */
    public function carryOptions(SessionYear $from, ?SessionYear $to, array $chosen = []): array
    {
        foreach (self::NEEDS as $key => $needs) {
            if (in_array($key, $chosen, true)) {
                $chosen = array_values(array_unique([...$chosen, ...$needs]));
            }
        }

        $options = [];
        foreach (self::CARRY as $key => [$label]) {
            $source = $this->count($key, $from->id);
            $existing = $to ? $this->count($key, $to->id) : 0;
            $isChosen = in_array($key, $chosen, true);
            $options[] = [
                'key' => $key,
                'label' => __($label),
                'source' => $source,
                'existing' => $existing,
                'chosen' => $isChosen,
                'copies' => $isChosen && $source > 0 && $existing === 0,
            ];
        }

        return $options;
    }

    /**
     * The preview shown before the switch.
     *
     * @param  list<string>  $chosen
     * @return array{carry: list<array<string, mixed>>, promoted: int, leaving: int, learners: int, leaver_until: string}
     */
    public function preview(SessionYear $from, SessionYear $to, array $chosen): array
    {
        return [
            'carry' => $this->carryOptions($from, $to, $chosen),
            'promoted' => StudentSessions::where('session_year_id', $to->id)->where('status', 1)->count(),
            'leaving' => StudentSessions::where('session_year_id', $to->id)->where('status', 0)->count(),
            'learners' => StudentSessions::where('session_year_id', $from->id)->where('status', 1)->distinct()->count('student_id'),
            // Leavers read their records until then (LeaverState, 1.21).
            'leaver_until' => app(LeaverState::class)->defaultUntil($to),
        ];
    }

    /**
     * Year-end checks for the current year, each linking to the page that settles
     * it. They are advice: none of them stops the switch.
     *
     * @return list<array{key: string, title: string, detail: string, ok: bool, url: ?string}>
     */
    public function yearEndChecklist(SessionYear $current, ?SessionYear $next, User $user): array
    {
        $unpublished = Exam::where('session_year_id', $current->id)->where('publish', 0)->count();
        $pendingLeave = Leave::where('session_year_id', $current->id)->where('status', 0)->count();
        $learners = StudentSessions::where('session_year_id', $current->id)->where('status', 1)->distinct()->count('student_id');
        // Placed means this year's own learners have a row next year; new
        // admissions next year do not make up for learners still unplaced.
        $placed = $next
            ? StudentSessions::where('session_year_id', $next->id)
                ->whereIn('student_id', StudentSessions::select('student_id')->where('session_year_id', $current->id)->where('status', 1))
                ->distinct()->count('student_id')
            : 0;
        $url = fn (string $route, string $permission) => Route::has($route) && $user->can($permission) ? route($route) : null;
        // Promoting by grade needs each class's grade (1.18, 1.21).
        $ungraded = ClassSchool::query()->whereNull('grade_level_id')
            ->whereIn('id', ClassSection::query()->whereIn('id', Enrolment::query()->where('session_year_id', $current->id)->select('class_section_id'))->select('class_id'))->count();

        return [
            [
                'key' => 'results',
                'title' => __('new_year_check_results'),
                'detail' => $unpublished ? trans_choice('new_year_check_results_open', $unpublished, ['count' => $unpublished]) : __('new_year_check_results_ok'),
                'ok' => $unpublished === 0,
                'url' => $url('exams.index', 'exam-create'),
            ],
            [
                'key' => 'promotions',
                'title' => __('new_year_check_promotions'),
                'detail' => $next
                    ? __('new_year_check_promotions_detail', ['placed' => $placed, 'learners' => $learners, 'year' => $next->name])
                    : __('new_year_check_promotions_no_year'),
                'ok' => $next !== null && $placed >= $learners,
                'url' => $url('promotions.by-grade', 'promote-student-list'),
            ],
            [
                'key' => 'grades',
                'title' => __('new_year_check_grades'),
                'detail' => $ungraded ? trans_choice('new_year_check_grades_open', $ungraded, ['count' => $ungraded]) : __('new_year_check_grades_ok'),
                'ok' => $ungraded === 0,
                'url' => $url('class.index', 'class-list'),
            ],
            [
                'key' => 'leave',
                'title' => __('new_year_check_leave'),
                'detail' => $pendingLeave ? trans_choice('new_year_check_leave_open', $pendingLeave, ['count' => $pendingLeave]) : __('new_year_check_leave_ok'),
                'ok' => $pendingLeave === 0,
                'url' => $url('leave-request.index', 'leave-approve'),
            ],
            [
                'key' => 'fees',
                'title' => __('new_year_check_fees'),
                'detail' => __('new_year_check_fees_detail', ['year' => $current->name]),
                'ok' => true,
                'url' => $url('fees.paid.index', 'fees-paid'),
            ],
        ];
    }

    /**
     * Run the chosen transfers into the new year (only where it has nothing of
     * that kind yet) and make it current, in one transaction.
     *
     * @param  list<string>  $chosen
     * @return array{copied: list<string>, skipped: list<string>, welcomed: int}
     */
    public function switchTo(SessionYear $from, SessionYear $to, array $chosen, bool $welcome = false): array
    {
        if ($to->id === $from->id) {
            throw new \DomainException(__('new_year_already_current', ['year' => $to->name]));
        }
        if ($to->end_date < now()->toDateString()) {
            throw new \DomainException(__('session_year_has_ended'));
        }

        return DB::transaction(function () use ($from, $to, $chosen, $welcome) {
            $copied = [];
            $skipped = [];
            $after = [];
            foreach ($this->carryOptions($from, $to, $chosen) as $option) {
                if (! $option['chosen']) {
                    continue;
                }
                if (! $option['copies']) {
                    $skipped[] = $option['key'];

                    continue;
                }
                if (in_array($option['key'], self::AFTER_SWITCH, true)) {
                    $after[] = $option['key'];

                    continue;
                }
                app(self::CARRY[$option['key']][1])->execute($from->id, $to->id);
                $copied[] = $option['key'];
            }

            $this->years->makeCurrent($to);
            foreach ($after as $key) {
                app(self::CARRY[$key][1])->execute($from->id, $to->id);
                $copied[] = $key;
            }

            $welcomed = $welcome ? $this->welcome($to) : 0;

            return ['copied' => $copied, 'skipped' => $skipped, 'welcomed' => $welcomed];
        });
    }

    /**
     * "Welcome to the new year" (Action Plan 1.21): one notice to the learners
     * placed in the new year and their current guardians, sent after the
     * switch commits. Names no learner and no homeroom.
     *
     * @return int how many people it goes to
     */
    public function welcome(SessionYear $year): int
    {
        $first = max(Carbon::parse($year->start_date)->toDateString(), now()->toDateString());
        $leaving = StudentSessions::query()->where('session_year_id', $year->id)->where('status', 0)->select('student_id');
        $learners = Enrolment::query()->where('session_year_id', $year->id)->onDate($first)
            ->whereNotIn('learner_id', $leaving)->pluck('learner_id')->unique();
        $users = [];
        foreach (Students::query()->whereIn('id', $learners)->get() as $learner) {
            $users[] = (int) $learner->user_id;
            array_push($users, ...app(GuardianService::class)->guardianUserIds($learner)->all());
        }
        $users = array_values(array_unique(array_filter($users)));
        if ($users === []) {
            return 0;
        }
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice(self::WELCOME, __('new_year_welcome_title', ['year' => $year->name]),
            __('new_year_welcome_body', ['year' => $year->name, 'date' => Carbon::parse($year->start_date)->format('j M Y')]), '/academics/timetable');
        $notifier->assign($notice, $users);

        return count($users);
    }

    private function count(string $key, int $yearId): int
    {
        if (isset(self::MODULES[$key]) && ! app(ModuleGate::class)->allows(self::MODULES[$key])) {
            return 0;
        }

        return match ($key) {
            'curriculum' => CurriculumPlan::where('session_year_id', $yearId)->count(),
            'subjects' => ClassSubject::where('session_year_id', $yearId)->count(),
            'teachers' => ClassTeacher::where('session_year_id', $yearId)->count() + SubjectTeacher::where('session_year_id', $yearId)->count(),
            'timetable' => Timetable::where('session_year_id', $yearId)->whereNull('timetable_lesson_id')->count(),
            'grading' => Grade::where('session_year_id', $yearId)->count(),
            'leave' => LeaveMaster::where('session_year_id', $yearId)->count(),
            'teaching_groups' => TeachingGroup::where('session_year_id', $yearId)->where('active', true)->count(),
            'activity_groups' => ActivityGroup::where('session_year_id', $yearId)->where('active', true)->count(),
            'bell_schedules' => BellSchedule::where('session_year_id', $yearId)->count(),
            'houses' => HouseMembership::where('session_year_id', $yearId)->where('active', true)->count(),
            'award_windows' => AwardWindow::where('session_year_id', $yearId)->count(),
            'discipline' => DisciplineFramework::where('session_year_id', $yearId)->count(),
        };
    }

    /** "2026" becomes "2027", "2022-23" becomes "2023-24"; a name without a year becomes the start year. */
    private function moveName(string $name, int $shift, string $start): string
    {
        $moved = preg_replace_callback('/\b((?:19|20)\d{2})([-\/])(\d{2})\b/', fn ($m) => ($m[1] + $shift).$m[2].str_pad((string) (((int) $m[3] + $shift) % 100), 2, '0', STR_PAD_LEFT), $name, -1, $pairs);
        if ($pairs === 0) {
            $moved = preg_replace_callback('/\b(?:19|20)\d{2}\b/', fn ($m) => (string) ($m[0] + $shift), $name, -1, $years);
            if ($years === 0) {
                return Carbon::parse($start)->format('Y');
            }
        }

        return (string) $moved;
    }
}
