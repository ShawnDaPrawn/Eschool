<?php

declare(strict_types=1);

namespace App\Services\Reading;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\Reading\ReadingAnswer;
use App\Models\Reading\ReadingBook;
use App\Models\Reading\ReadingBookText;
use App\Models\Reading\ReadingDay;
use App\Models\Reading\ReadingHiddenItem;
use App\Models\Reading\ReadingProgress;
use App\Models\Reading\ReadingQuestion;
use App\Models\Reading\ReadingShelf;
use App\Models\Reading\ReadingShelfBook;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Services\Academics\AcademicAuthority;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What a learner reads (Action Plan 4.4): the shelves HQ made for their grade
 * that their school has not hidden, with only the books HQ has cleared as
 * public domain in South Africa; each book's pages from the server's cache;
 * their own progress and reading time; and the comprehension questions. Every
 * learner row is the school's and the learner's own: another learner's
 * progress or answers are never read or written here.
 */
class ReadingLibrary
{
    public const MAX_SHELVES = 50;

    public function __construct(private readonly ReadingFetcher $fetcher) {}

    public function shelves(Students $learner): array
    {
        $grade = $this->gradeOrder($learner);
        $shelves = $grade === null ? collect() : $this->visibleShelves($grade);
        $entries = $this->shelfBooks($shelves->pluck('id')->all());
        $books = ReadingBook::query()->whereIn('id', $entries->pluck('reading_book_id')->unique())->get()->keyBy('id');
        $texts = ReadingBookText::query()->whereIn('reading_book_id', $books->keys())->get()->keyBy('reading_book_id');
        $progress = ReadingProgress::query()->where('learner_id', $learner->id)->whereIn('reading_book_id', $books->keys())->get()->keyBy('reading_book_id');

        return [
            'grade_set' => $grade !== null,
            'shelves' => $shelves->map(fn (ReadingShelf $shelf) => ['id' => (int) $shelf->id, 'name' => $shelf->name, 'description' => $shelf->description,
                'books' => $entries->where('reading_shelf_id', $shelf->id)->map(fn ($entry) => $this->card($books[$entry->reading_book_id], $texts->get($entry->reading_book_id), $progress->get($entry->reading_book_id)))->values()->all()])
                ->values()->all(),
            'reading' => $this->summary($learner),
            'ai_checks' => app(ReadingAiChecks::class)->available(),
        ];
    }

    public function book(Students $learner, int $id): array
    {
        $book = $this->visibleBook($learner, $id);
        $text = ReadingBookText::query()->where('reading_book_id', $book->id)->first();
        $progress = ReadingProgress::query()->where('learner_id', $learner->id)->where('reading_book_id', $book->id)->first();
        $questions = ReadingQuestion::query()->where('reading_book_id', $book->id)->where('active', true)->get(['id', 'after_page']);
        $answered = ReadingAnswer::query()->where('learner_id', $learner->id)->whereIn('reading_question_id', $questions->pluck('id'))->count();

        return $this->card($book, $text, $progress) + [
            'author_birth_year' => $book->author_birth_year, 'author_death_year' => $book->author_death_year, 'summary' => $book->summary,
            'word_count' => $text?->word_count ?? $book->word_count,
            'checks' => ['total' => $questions->count(), 'open' => $questions->filter(fn ($q) => $this->unlocked($q, $progress))->count(), 'answered' => $answered],
        ];
    }

    /** One page of the book, fetching the book from the mirror the first time anyone reads it. */
    public function page(Students $learner, int $id, int $page): array
    {
        $book = $this->visibleBook($learner, $id);
        try {
            $text = $this->fetcher->ensure($book);
        } catch (ReadingFetchFailed) {
            abort(503, __('This book is not ready yet. Please try again a little later.'), ['Retry-After' => (string) config('reading.retry_after_seconds')]);
        }
        abort_unless($page >= 1 && $page <= (int) $text->page_count, 404);

        return ['book_id' => (int) $book->id, 'page' => $page, 'page_count' => (int) $text->page_count, 'text' => $this->fetcher->page($text, $page)];
    }

    /**
     * Save where the learner is and add the time they read. Reading to the
     * last page finishes the book (once). Badges follow, outside the save.
     */
    public function progress(Students $learner, int $id, array $data): array
    {
        $book = $this->visibleBook($learner, $id);
        $text = ReadingBookText::query()->where('reading_book_id', $book->id)->first();
        abort_unless($text !== null && $this->fetcher->ready($text), 409, __('Open the book before saving your place.'));
        $pages = (int) $text->page_count;
        $page = (int) $data['page'];
        if ($page > $pages) {
            abort(422, __('That page is not in this book.'));
        }
        $seconds = min((int) $data['seconds'], (int) config('reading.max_seconds_per_report'));
        $today = app(AcademicAuthority::class)->today();

        [$row, $finishedNow, $newDay] = DB::transaction(function () use ($learner, $book, $page, $pages, $seconds, $today): array {
            $row = $this->progressRow($learner, $book);
            $day = $this->dayRow($learner, $today);
            $room = max(0, (int) config('reading.max_seconds_per_day') - $day->seconds);
            $added = min($seconds, $room);
            $threshold = (int) config('reading.reading_day_seconds');
            $wasDay = $day->seconds >= $threshold;
            if ($added > 0) {
                $day->fill(['seconds' => $day->seconds + $added])->save();
            }
            $finishedNow = $row->finished_at === null && $page === $pages;
            $row->fill(['page' => $page, 'furthest_page' => max($row->furthest_page, $page), 'seconds_read' => $row->seconds_read + $added,
                'finished_at' => $finishedNow ? now() : $row->finished_at, 'revision' => $row->revision + 1])->save();

            return [$row, $finishedNow, ! $wasDay && $day->seconds >= $threshold];
        }, 3);

        $badges = app(ReadingBadges::class)->after($learner, $book, $finishedNow, $newDay, $today);

        return ['progress' => $this->progressView($row, $pages), 'reading' => $this->summary($learner), 'badges' => $badges];
    }

    /** The open questions for this learner (no answers), and the result of each one they have answered. */
    public function questions(Students $learner, int $id): array
    {
        $book = $this->visibleBook($learner, $id);
        $progress = ReadingProgress::query()->where('learner_id', $learner->id)->where('reading_book_id', $book->id)->first();
        $questions = ReadingQuestion::query()->where('reading_book_id', $book->id)->where('active', true)->orderByRaw('after_page is null')->orderBy('after_page')->orderBy('id')->get();
        $open = $questions->filter(fn (ReadingQuestion $q) => $this->unlocked($q, $progress))->values();
        $answers = ReadingAnswer::query()->where('learner_id', $learner->id)->whereIn('reading_question_id', $open->pluck('id'))->get()->keyBy('reading_question_id');

        return [
            'book_id' => (int) $book->id,
            'questions' => $open->map(fn (ReadingQuestion $q) => ['id' => (int) $q->id, 'after_page' => $q->after_page, 'prompt' => $q->prompt, 'options' => array_values($q->options),
                'result' => isset($answers[$q->id]) ? $this->result($q, $answers[$q->id]) : null])->all(),
            'locked' => $questions->count() - $open->count(),
        ];
    }

    /** Mark an answer on the server. The first answer stands; asking again returns it. */
    public function answer(Students $learner, int $questionId, int $choice): array
    {
        $question = ReadingQuestion::query()->where('active', true)->findOrFail($questionId);
        $book = $this->visibleBook($learner, (int) $question->reading_book_id);
        $progress = ReadingProgress::query()->where('learner_id', $learner->id)->where('reading_book_id', $book->id)->first();
        abort_unless($this->unlocked($question, $progress), 404);
        abort_unless($choice >= 0 && $choice < count($question->options), 422, __('Choose one of the answers.'));
        $existing = ReadingAnswer::query()->where('learner_id', $learner->id)->where('reading_question_id', $question->id)->first();
        if ($existing === null) {
            try {
                $existing = ReadingAnswer::query()->create(['learner_id' => $learner->id, 'reading_question_id' => $question->id, 'choice' => $choice, 'correct' => $choice === (int) $question->answer]);
            } catch (UniqueConstraintViolationException) {
                $existing = ReadingAnswer::query()->where('learner_id', $learner->id)->where('reading_question_id', $question->id)->firstOrFail();
            }
        }

        return ['question_id' => (int) $question->id] + $this->result($question, $existing);
    }

    /** The grade (R = 0 to 12) of the learner's homeroom this school year, as school stages read it. */
    public function gradeOrder(Students $learner): ?int
    {
        $year = (int) schoolSetting('session_year');
        $section = StudentSessions::query()->where('student_id', $learner->id)->where('session_year_id', $year)->value('class_section_id');
        $class = $section !== null ? ClassSection::query()->whereKey($section)->value('class_id') : $learner->class_id;
        $grade = $class !== null ? ClassSchool::query()->whereKey($class)->value('grade_level_id') : null;

        return $grade === null ? null : (int) GradeLevel::query()->whereKey($grade)->value('sort_order');
    }

    /** A book this learner may open: cleared, on a shelf for their grade that the school shows, and not hidden. Otherwise 404. */
    public function visibleBook(Students $learner, int $id): ReadingBook
    {
        $grade = $this->gradeOrder($learner);
        abort_if($grade === null, 404);
        $shelves = $this->visibleShelves($grade)->pluck('id')->all();
        abort_unless(ReadingShelfBook::query()->whereIn('reading_shelf_id', $shelves)->where('reading_book_id', $id)->exists(), 404);
        abort_if(ReadingHiddenItem::query()->where('kind', 'book')->where('item_id', $id)->exists(), 404);
        $book = ReadingBook::query()->findOrFail($id);
        abort_unless($book->cleared(), 404);

        return $book;
    }

    /** @return Collection<int, ReadingShelf> */
    private function visibleShelves(int $grade): Collection
    {
        $hidden = ReadingHiddenItem::query()->where('kind', 'shelf')->pluck('item_id')->all();

        return ReadingShelf::query()->where('active', true)->where('grade_from', '<=', $grade)->where('grade_to', '>=', $grade)
            ->whereNotIn('id', $hidden)->orderBy('grade_from')->orderBy('name')->orderBy('id')->limit(self::MAX_SHELVES)->get();
    }

    /** Cleared, not hidden books on these shelves, in shelf order. */
    private function shelfBooks(array $shelves): Collection
    {
        $hidden = ReadingHiddenItem::query()->where('kind', 'book')->pluck('item_id')->all();

        return ReadingShelfBook::query()->whereIn('reading_shelf_id', $shelves)->whereNotIn('reading_book_id', $hidden)
            ->whereHas('book', fn ($q) => $q->where('rights_status', ReadingBook::CLEARED))
            ->orderBy('reading_shelf_id')->orderBy('position')->get();
    }

    private function card(ReadingBook $book, ?ReadingBookText $text, ?ReadingProgress $progress): array
    {
        $pages = $text !== null && $text->state === 'ready' ? (int) $text->page_count : null;

        return ['id' => (int) $book->id, 'title' => $book->title, 'author' => $book->author, 'language' => $book->language, 'subjects' => array_values($book->subjects ?? []),
            'reading_level' => $book->reading_level, 'reading_level_label' => __(ReadingCatalogue::LEVELS[$book->reading_level] ?? $book->reading_level),
            'page_count' => $pages, 'progress' => $progress === null ? null : $this->progressView($progress, $pages)];
    }

    private function progressView(ReadingProgress $row, ?int $pages): array
    {
        return ['page' => (int) $row->page, 'furthest_page' => (int) $row->furthest_page, 'page_count' => $pages, 'minutes_read' => intdiv((int) $row->seconds_read, 60),
            'finished' => $row->finished_at !== null, 'finished_on' => $row->finished_at?->toDateString()];
    }

    /** The learner's own reading this school-local week. Never compared with anyone else. */
    private function summary(Students $learner): array
    {
        $today = CarbonImmutable::parse(app(AcademicAuthority::class)->today());
        $start = $today->startOfWeek()->toDateString();
        $days = ReadingDay::query()->where('learner_id', $learner->id)->whereBetween('read_on', [$start, $today->toDateString()])->get();

        return ['week_start' => $start, 'minutes_this_week' => intdiv((int) $days->sum('seconds'), 60),
            'days_this_week' => $days->where('seconds', '>=', (int) config('reading.reading_day_seconds'))->count(),
            'books_finished' => ReadingProgress::query()->where('learner_id', $learner->id)->whereNotNull('finished_at')->count()];
    }

    private function unlocked(ReadingQuestion $question, ?ReadingProgress $progress): bool
    {
        if ($progress === null) {
            return false;
        }

        return $question->after_page === null ? $progress->finished_at !== null : $progress->furthest_page >= $question->after_page;
    }

    private function result(ReadingQuestion $question, ReadingAnswer $answer): array
    {
        return ['choice' => (int) $answer->choice, 'correct' => (bool) $answer->correct, 'answer' => (int) $question->answer, 'explanation' => $question->explanation];
    }

    private function progressRow(Students $learner, ReadingBook $book): ReadingProgress
    {
        $row = ReadingProgress::query()->where('learner_id', $learner->id)->where('reading_book_id', $book->id)->lockForUpdate()->first();
        if ($row !== null) {
            return $row;
        }
        try {
            return DB::transaction(fn () => ReadingProgress::query()->create(['learner_id' => $learner->id, 'reading_book_id' => $book->id, 'page' => 1, 'furthest_page' => 1, 'seconds_read' => 0, 'revision' => 0]));
        } catch (UniqueConstraintViolationException) {
            return ReadingProgress::query()->where('learner_id', $learner->id)->where('reading_book_id', $book->id)->lockForUpdate()->firstOrFail();
        }
    }

    private function dayRow(Students $learner, string $today): ReadingDay
    {
        $row = ReadingDay::query()->where('learner_id', $learner->id)->where('read_on', $today)->lockForUpdate()->first();
        if ($row !== null) {
            return $row;
        }
        try {
            return DB::transaction(fn () => ReadingDay::query()->create(['learner_id' => $learner->id, 'read_on' => $today, 'seconds' => 0]));
        } catch (UniqueConstraintViolationException) {
            return ReadingDay::query()->where('learner_id', $learner->id)->where('read_on', $today)->lockForUpdate()->firstOrFail();
        }
    }
}
