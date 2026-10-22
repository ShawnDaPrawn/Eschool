<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Actions\Transfers\TransferClassFeeType;
use App\Actions\Transfers\TransferClassSubject;
use App\Actions\Transfers\TransferClassTeacherAndSubjectTeacher;
use App\Actions\Transfers\TransferClassTimetable;
use App\Actions\Transfers\TransferExamGrades;
use App\Actions\Transfers\TransferLeaveSettings;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\Exam;
use App\Models\FeesClass;
use App\Models\Grade;
use App\Models\Leave;
use App\Models\LeaveMaster;
use App\Models\SessionYear;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Models\Timetable;
use App\Models\User;
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
        'subjects' => ['new_year_carry_subjects', TransferClassSubject::class],
        'teachers' => ['new_year_carry_teachers', TransferClassTeacherAndSubjectTeacher::class],
        'timetable' => ['new_year_carry_timetable', TransferClassTimetable::class],
        'grading' => ['new_year_carry_grading', TransferExamGrades::class],
        'fees' => ['new_year_carry_fees', TransferClassFeeType::class],
        'leave' => ['new_year_carry_leave', TransferLeaveSettings::class],
    ];

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
     * @return array{name: string, start_date: string, end_date: string, fee_due_date: string, fee_due_charges: string, terms: list<array{name: string, start_date: string, end_date: string}>}
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
        $feeDue = $move($current->fee_due_date);
        if ($feeDue === '' || $feeDue < $start || $feeDue > $end) {
            $feeDue = Carbon::parse($start)->addMonth()->toDateString();
        }

        $terms = $current->semesters()->orderBy('start_date')->get()
            ->map(fn ($term) => ['name' => $term->name, 'start_date' => $move($term->start_date), 'end_date' => $move($term->end_date)])
            ->all();
        if ($terms === []) {
            $terms = array_map(fn ($n) => ['name' => __('new_year_term_name', ['number' => $n]), 'start_date' => '', 'end_date' => ''], range(1, 4));
        }

        return [
            'name' => $this->moveName($current->name, $shift, $start),
            'start_date' => $start,
            'end_date' => $end,
            'fee_due_date' => $feeDue,
            'fee_due_charges' => (string) ($current->fee_due_charges ?? 0),
            'terms' => $terms,
        ];
    }

    /**
     * Create next year without touching the current one: no transfers, not current.
     *
     * @param  array{name: string, start_date: string, end_date: string, fee_due_date: string, fee_due_charges: numeric, terms?: list<array{name: string, start_date: string, end_date: string}>}  $data
     */
    public function create(array $data): SessionYear
    {
        $dmy = fn (string $date) => Carbon::parse($date)->format('d-m-Y');
        $terms = array_values(array_filter($data['terms'] ?? [], fn ($term) => ! empty($term['start_date']) && ! empty($term['end_date'])));

        return $this->years->store([
            'name' => $data['name'],
            'start_date' => $dmy($data['start_date']),
            'end_date' => $dmy($data['end_date']),
            'fees_due_date' => $dmy($data['fee_due_date']),
            'fees_due_charges' => $data['fee_due_charges'],
            'fees_installment' => '0',
            'set_as_current' => '0',
            'semester_data' => array_map(fn ($term) => [
                'name' => $term['name'],
                'start_date' => $dmy($term['start_date']),
                'end_date' => $dmy($term['end_date']),
            ], $terms),
        ]);
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
     * @return array{carry: list<array<string, mixed>>, promoted: int, leaving: int, learners: int}
     */
    public function preview(SessionYear $from, SessionYear $to, array $chosen): array
    {
        return [
            'carry' => $this->carryOptions($from, $to, $chosen),
            'promoted' => StudentSessions::where('session_year_id', $to->id)->where('status', 1)->count(),
            'leaving' => StudentSessions::where('session_year_id', $to->id)->where('status', 0)->count(),
            'learners' => StudentSessions::where('session_year_id', $from->id)->where('status', 1)->distinct()->count('student_id'),
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
                'url' => $url('promote-student.index', 'promote-student-list'),
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
     * @return array{copied: list<string>, skipped: list<string>}
     */
    public function switchTo(SessionYear $from, SessionYear $to, array $chosen): array
    {
        if ($to->id === $from->id) {
            throw new \DomainException(__('new_year_already_current', ['year' => $to->name]));
        }
        if ($to->end_date < now()->toDateString()) {
            throw new \DomainException(__('session_year_has_ended'));
        }

        return DB::transaction(function () use ($from, $to, $chosen) {
            $copied = [];
            $skipped = [];
            foreach ($this->carryOptions($from, $to, $chosen) as $option) {
                if (! $option['chosen']) {
                    continue;
                }
                if (! $option['copies']) {
                    $skipped[] = $option['key'];

                    continue;
                }
                app(self::CARRY[$option['key']][1])->execute($from->id, $to->id);
                $copied[] = $option['key'];
            }

            $this->years->makeCurrent($to);

            return ['copied' => $copied, 'skipped' => $skipped];
        });
    }

    private function count(string $key, int $yearId): int
    {
        return match ($key) {
            'subjects' => ClassSubject::where('session_year_id', $yearId)->count(),
            'teachers' => ClassTeacher::where('session_year_id', $yearId)->count() + SubjectTeacher::where('session_year_id', $yearId)->count(),
            'timetable' => Timetable::where('session_year_id', $yearId)->count(),
            'grading' => Grade::where('session_year_id', $yearId)->count(),
            'fees' => FeesClass::where('session_year_id', $yearId)->count(),
            'leave' => LeaveMaster::where('session_year_id', $yearId)->count(),
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
