<?php

declare(strict_types=1);

namespace App\Services\Learning\Diary;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\Event;
use App\Models\Holiday;
use App\Models\Learning\DiaryItemState;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\StudentSubject;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * School items for one learner and a date range, worked out on every read from
 * the sources themselves (engineering/specs/learning/learner-diary.md), so a
 * moved due date, a deleted assignment or a class change shows at once. Each
 * source applies the same audience rules as its own screens. The learner's own
 * layer (done, reminder, private note) is merged in from diary_item_states.
 */
class DiarySchoolItems
{
    public const SOURCES = ['homework', 'holiday', 'event'];

    /** Unsubmitted homework due this many days before today still shows as overdue. */
    private const OVERDUE_DAYS = 30;

    public function __construct(private readonly DiaryCipher $cipher) {}

    /** @return list<array<string, mixed>> */
    public function between(Students $learner, CarbonImmutable $from, CarbonImmutable $to, CarbonImmutable $today): array
    {
        $items = $this->homework($learner, $from, $to, $today)
            ->concat($this->holidays($from, $to))
            ->concat($this->events($from, $to));

        return $this->withStates($learner, $items)
            ->sortBy(fn ($item) => $item['date'].' '.($item['due_at'] ?? '').' '.$item['source'].' '.str_pad((string) $item['id'], 12, '0', STR_PAD_LEFT))
            ->values()
            ->all();
    }

    /** Whether the item currently applies to the learner (writes to an item that does not are refused as not found). */
    public function applies(Students $learner, string $source, int $id): bool
    {
        return match ($source) {
            'homework' => $this->forLearner($learner, $this->homeworkQuery($learner)->whereKey($id)->get())->isNotEmpty(),
            'holiday' => Holiday::query()->where('session_year_id', (int) schoolSetting('session_year'))->whereKey($id)->exists(),
            'event' => Event::query()->where('session_year_id', (int) schoolSetting('session_year'))->whereKey($id)->exists(),
            default => false,
        };
    }

    /**
     * The titles of the items [states] point at that still apply and are not
     * handed in, keyed "source:id".
     *
     * @return array<string, string>
     */
    public function openTitles(Students $learner, Collection $states): array
    {
        $ids = fn (string $source) => $states->where('source', $source)->pluck('source_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $titles = [];
        if (($homework = $ids('homework')) !== []) {
            $handedIn = AssignmentSubmission::query()->where('student_id', $learner->id)->whereIn('assignment_id', $homework)
                ->whereIn('status', [0, 1, 3])->pluck('assignment_id')->map(fn ($id) => (int) $id)->all();
            $rows = $this->homeworkQuery($learner)->whereKey($homework)->get(['id', 'name', 'subject_id', 'class_section_id', 'term_id']);
            foreach ($this->forLearner($learner, $rows) as $row) {
                if (! in_array((int) $row->id, $handedIn, true)) {
                    $titles['homework:'.$row->id] = (string) $row->name;
                }
            }
        }
        $year = (int) schoolSetting('session_year');
        foreach (['holiday' => Holiday::class, 'event' => Event::class] as $source => $model) {
            if (($list = $ids($source)) !== []) {
                foreach ($model::query()->where('session_year_id', $year)->whereKey($list)->get(['id', 'title']) as $row) {
                    $titles[$source.':'.$row->id] = (string) $row->title;
                }
            }
        }

        return $titles;
    }

    private function homework(Students $learner, CarbonImmutable $from, CarbonImmutable $to, CarbonImmutable $today): Collection
    {
        $carryOverdue = $today->betweenIncluded($from, $to);
        $rows = $this->homeworkQuery($learner)
            ->with('subject:id,name')
            ->where(function ($q) use ($from, $to, $today, $carryOverdue) {
                $q->whereBetween('due_date', [$from->startOfDay()->format('Y-m-d H:i:s'), $to->endOfDay()->format('Y-m-d H:i:s')]);
                if ($carryOverdue) {
                    $q->orWhereBetween('due_date', [$today->subDays(self::OVERDUE_DAYS)->startOfDay()->format('Y-m-d H:i:s'), $today->startOfDay()->format('Y-m-d H:i:s')]);
                }
            })
            ->orderBy('due_date')
            ->limit(500)
            ->get();
        $rows = $this->forLearner($learner, $rows);
        $submissions = AssignmentSubmission::query()
            ->where('student_id', $learner->id)
            ->whereIn('assignment_id', $rows->pluck('id'))
            ->get()
            ->keyBy('assignment_id');

        return $rows->map(function (Assignment $row) use ($submissions, $today, $from, $carryOverdue) {
            $submission = $submissions->get($row->id);
            $due = CarbonImmutable::parse($row->due_date, $today->getTimezone());
            $submitted = $submission !== null && in_array((int) $submission->status, [0, 1, 3], true);
            $overdue = ! $submitted && $due->lessThan($today->startOfDay());
            // Carried in only because it might be overdue: once handed in, it stays on its own day.
            if (! $overdue && $due->lessThan($from->startOfDay())) {
                return null;
            }

            return [
                'source' => 'homework',
                'id' => (int) $row->id,
                'title' => (string) $row->name,
                // Late work moves to today only on a page that shows today and
                // within the carry window; any other range shows its due day.
                'date' => $overdue && $carryOverdue && ! $due->lessThan($today->subDays(self::OVERDUE_DAYS)->startOfDay())
                    ? $today->toDateString() : $due->toDateString(),
                'due_at' => $due->format('Y-m-d H:i'),
                'end_date' => null,
                'subject' => $row->subject ? ['id' => (int) $row->subject->id, 'name' => (string) $row->subject->name] : null,
                'homework' => [
                    'submitted' => $submitted,
                    'status' => $submission ? match ((int) $submission->status) {
                        1 => 'accepted', 2 => 'rejected', 3 => 'resubmitted', default => 'in_review',
                    } : null,
                    'points' => $submission && (int) $submission->status === 1 && $submission->points !== null ? (int) $submission->points : null,
                    'total' => $row->points !== null && $row->points !== '' ? (int) $row->points : null,
                    'overdue' => $overdue,
                ],
                'done_by_school' => $submitted,
            ];
        })->filter()->values();
    }

    /** Homework set for the learner's class section(s) this session year, before the elective rule. */
    private function homeworkQuery(Students $learner)
    {
        $year = (int) schoolSetting('session_year');
        $sections = StudentSessions::query()->where('student_id', $learner->id)->where('session_year_id', $year)
            ->pluck('class_section_id')->push($learner->class_section_id)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        return Assignment::query()->where('session_year_id', $year)->whereIn('class_section_id', $sections);
    }

    /**
     * The homework among [rows] this learner gets, by the rule homework
     * notifications use: a subject that is an elective in the assignment's
     * class (and term) only for learners who take it in that class section
     * (and term).
     */
    private function forLearner(Students $learner, Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }
        $year = (int) schoolSetting('session_year');
        $classes = ClassSection::query()->whereIn('id', $rows->pluck('class_section_id')->unique())->pluck('class_id', 'id');
        $offerings = ClassSubject::query()->whereIn('class_id', $classes->values()->unique())->where('session_year_id', $year)
            ->whereIn('subject_id', $rows->pluck('subject_id')->unique())->orderBy('id')->get(['class_id', 'subject_id', 'term_id', 'type']);
        $taken = StudentSubject::query()->where('student_id', $learner->id)->where('session_year_id', $year)
            ->get(['subject_id', 'class_section_id', 'term_id']);
        $sameTerm = fn ($term, $row) => $row->term_id === null || (int) $term === (int) $row->term_id;

        return $rows->filter(function (Assignment $row) use ($classes, $offerings, $taken, $sameTerm) {
            $classId = (int) $classes->get($row->class_section_id);
            $offering = $offerings->first(fn ($o) => (int) $o->class_id === $classId
                && (int) $o->subject_id === (int) $row->subject_id && $sameTerm($o->term_id, $row));
            if ($offering?->type !== 'Elective') {
                return true;
            }

            return $taken->contains(fn ($t) => (int) $t->subject_id === (int) $row->subject_id
                && (int) $t->class_section_id === (int) $row->class_section_id && $sameTerm($t->term_id, $row));
        })->values();
    }

    private function holidays(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Holiday::query()->where('session_year_id', (int) schoolSetting('session_year'))->whereBetween('date', [$from->toDateString(), $to->toDateString()])->orderBy('date')->limit(200)->get()
            ->map(fn (Holiday $row) => [
                'source' => 'holiday', 'id' => (int) $row->id, 'title' => (string) $row->title,
                'date' => CarbonImmutable::parse($row->date)->toDateString(), 'due_at' => null, 'end_date' => null,
                'subject' => null, 'homework' => null, 'done_by_school' => false,
            ]);
    }

    private function events(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Event::query()
            ->where('session_year_id', (int) schoolSetting('session_year'))
            ->whereDate('start_date', '<=', $to->toDateString())
            ->where(fn ($q) => $q->whereDate('end_date', '>=', $from->toDateString())
                ->orWhere(fn ($q) => $q->whereNull('end_date')->whereDate('start_date', '>=', $from->toDateString()))
                ->orWhere(fn ($q) => $q->where('end_date', '')->whereDate('start_date', '>=', $from->toDateString())))
            ->orderBy('start_date')->limit(200)->get()
            ->map(function (Event $row) use ($from) {
                $start = CarbonImmutable::parse($row->start_date);
                $end = filled($row->end_date) ? CarbonImmutable::parse($row->end_date) : null;

                return [
                    'source' => 'event', 'id' => (int) $row->id, 'title' => (string) $row->title,
                    'date' => ($start->lessThan($from) ? $from : $start)->toDateString(),
                    'due_at' => $row->start_time ? $start->toDateString().' '.substr((string) $row->start_time, 0, 5) : null,
                    'end_date' => $end && $end->greaterThan($start) ? $end->toDateString() : null,
                    'subject' => null, 'homework' => null, 'done_by_school' => false,
                ];
            });
    }

    private function withStates(Students $learner, Collection $items): Collection
    {
        $userId = (int) $learner->user_id;
        $states = DiaryItemState::query()->where('user_id', $userId)
            ->where(function ($q) use ($items) {
                foreach ($items->groupBy('source') as $source => $group) {
                    $q->orWhere(fn ($q) => $q->where('source', $source)->whereIn('source_id', $group->pluck('id')));
                }
                if ($items->isEmpty()) {
                    $q->whereRaw('1 = 0');
                }
            })
            ->get()
            ->keyBy(fn ($state) => $state->source.':'.$state->source_id);

        return $items->map(function (array $item) use ($states, $userId) {
            $state = $states->get($item['source'].':'.$item['id']);
            $item['state'] = [
                'done' => $item['done_by_school'] || ($state?->done_at !== null),
                'done_at' => $state?->done_at?->format('Y-m-d H:i'),
                'remind_at' => $state?->remind_at?->format('Y-m-d H:i'),
                'note' => $state ? $this->cipher->decrypt($userId, $state->note) : null,
            ];
            unset($item['done_by_school']);

            return $item;
        });
    }
}
