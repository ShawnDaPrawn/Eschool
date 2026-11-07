<?php

declare(strict_types=1);

namespace App\Services\Reading;

use App\Models\Academics\GradeLevel;
use App\Models\Reading\ReadingBook;
use App\Models\Reading\ReadingBookText;
use App\Models\Reading\ReadingQuestion;
use App\Models\Reading\ReadingRightsDecision;
use App\Models\Reading\ReadingShelf;
use App\Models\Reading\ReadingShelfBook;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;

/** What the /saas/reading pages show (Action Plan 4.4): catalogue rows and platform staff names only, never a school's people. */
class ReadingPages
{
    public const RIGHTS = ['undecided' => 'Not decided: not served', ReadingBook::CLEARED => 'Public domain in South Africa', 'not_cleared' => 'Not cleared: not served'];

    public const TEXT_STATES = ['missing' => 'Not fetched yet', 'ready' => 'Cached', 'failed' => 'Fetch failed'];

    public function index(User $actor): array
    {
        app(ReadingAccess::class)->hq($actor);
        $books = ReadingBook::query()->orderBy('title')->orderBy('id')->limit(1000)->get();
        $texts = ReadingBookText::query()->whereIn('reading_book_id', $books->pluck('id'))->get()->keyBy('reading_book_id');
        $shelves = ReadingShelf::query()->orderBy('grade_from')->orderBy('name')->limit(500)->get();
        $counts = ReadingShelfBook::query()->selectRaw('reading_shelf_id, COUNT(*) as total')->groupBy('reading_shelf_id')->pluck('total', 'reading_shelf_id');

        return [
            'books' => $books->map(fn (ReadingBook $b) => ['id' => (int) $b->id, 'gutenberg_id' => $b->gutenberg_id, 'title' => $b->title, 'author' => $b->author,
                'language' => $b->language, 'level' => ReadingCatalogue::LEVELS[$b->reading_level] ?? $b->reading_level, 'rights' => self::RIGHTS[$b->rights_status] ?? $b->rights_status,
                'cleared' => $b->cleared(), 'text' => self::TEXT_STATES[$texts->get($b->id)?->state ?? 'missing'] ?? '', 'pages' => $texts->get($b->id)?->page_count])->all(),
            'shelves' => $shelves->map(fn (ReadingShelf $s) => $this->shelfRow($s) + ['books' => (int) ($counts[$s->id] ?? 0)])->all(),
            'levels' => ReadingCatalogue::LEVELS,
            'gradeChoices' => $this->grades(),
            'mirror' => trim((string) config('reading.mirror_url')) !== '',
        ];
    }

    public function book(User $actor, int $id): array
    {
        app(ReadingAccess::class)->hq($actor);
        $book = ReadingBook::query()->findOrFail($id);
        $decisions = ReadingRightsDecision::query()->where('reading_book_id', $book->id)->orderByDesc('id')->limit(50)->get();
        $text = ReadingBookText::query()->where('reading_book_id', $book->id)->first();
        $fetcher = app(ReadingFetcher::class);
        $ready = $text !== null && $fetcher->ready($text);
        $questions = ReadingQuestion::query()->where('reading_book_id', $book->id)->orderBy('active', 'desc')->orderByRaw('after_page is null')->orderBy('after_page')->orderBy('id')->get();
        $shelves = ReadingShelf::query()->whereIn('id', ReadingShelfBook::query()->where('reading_book_id', $book->id)->select('reading_shelf_id'))->orderBy('name')->get();
        $names = $this->names($decisions->pluck('decided_by')->all());

        return [
            'book' => ['id' => (int) $book->id, 'gutenberg_id' => $book->gutenberg_id, 'title' => $book->title, 'author' => $book->author,
                'author_birth_year' => $book->author_birth_year, 'author_death_year' => $book->author_death_year, 'language' => $book->language,
                'subjects' => implode("\n", $book->subjects ?? []), 'reading_level' => $book->reading_level, 'summary' => $book->summary, 'revision' => $book->revision,
                'rights_status' => $book->rights_status, 'rights' => self::RIGHTS[$book->rights_status] ?? $book->rights_status, 'cleared' => $book->cleared(),
                'term_ended' => $book->author_death_year === null ? null : ReadingCatalogue::termEnded($book->author_death_year)],
            'decisions' => $decisions->map(fn (ReadingRightsDecision $d) => ['decision' => ReadingCatalogue::DECISIONS[$d->decision] ?? $d->decision, 'basis' => $d->basis,
                'author_death_year' => $d->author_death_year, 'translator_checked' => $d->translator_checked, 'by' => $names[$d->decided_by] ?? __('Former staff member'),
                'at' => $d->decided_at?->format('Y-m-d H:i'), 'current' => (int) $d->id === (int) $book->rights_decision_id])->all(),
            'text' => ['state' => self::TEXT_STATES[$text?->state ?? 'missing'] ?? '', 'ready' => $ready, 'pages' => $text?->page_count, 'words' => $text?->word_count,
                'removed_lines' => (int) ($text?->removed_lines ?? 0), 'fetched_at' => $text?->fetched_at?->format('Y-m-d H:i'),
                'error' => $text?->last_error === null ? null : (ReadingFetchFailed::REASONS[$text->last_error] ?? $text->last_error),
                'header' => $ready ? $fetcher->header($text) : null, 'preview' => $ready ? mb_substr($fetcher->page($text, 1), 0, 1500) : null],
            'questions' => $questions->map(fn (ReadingQuestion $q) => ['id' => (int) $q->id, 'prompt' => $q->prompt, 'options' => $q->options, 'answer' => (int) $q->answer,
                'explanation' => $q->explanation, 'after_page' => $q->after_page, 'active' => $q->active])->all(),
            'shelves' => $shelves->map(fn (ReadingShelf $s) => ['id' => (int) $s->id, 'name' => $s->name])->all(),
            'levels' => ReadingCatalogue::LEVELS,
            'decisionChoices' => ReadingCatalogue::DECISIONS,
            'mirror' => trim((string) config('reading.mirror_url')) !== '',
        ];
    }

    public function shelf(User $actor, int $id): array
    {
        app(ReadingAccess::class)->hq($actor);
        $shelf = ReadingShelf::query()->findOrFail($id);
        $entries = ReadingShelfBook::query()->where('reading_shelf_id', $shelf->id)->orderBy('position')->get();
        $books = ReadingBook::query()->whereIn('id', $entries->pluck('reading_book_id'))->get()->keyBy('id');

        return [
            'shelf' => $this->shelfRow($shelf) + ['grade_from' => $shelf->grade_from, 'grade_to' => $shelf->grade_to, 'revision' => $shelf->revision],
            'books' => $entries->map(fn ($e) => ['id' => (int) $e->reading_book_id, 'title' => $books[$e->reading_book_id]->title, 'author' => $books[$e->reading_book_id]->author,
                'rights' => self::RIGHTS[$books[$e->reading_book_id]->rights_status] ?? '', 'cleared' => $books[$e->reading_book_id]->cleared()])->all(),
            'bookList' => $entries->pluck('reading_book_id')->implode(', '),
            'gradeChoices' => $this->grades(),
        ];
    }

    private function shelfRow(ReadingShelf $shelf): array
    {
        $grades = $this->grades();

        return ['id' => (int) $shelf->id, 'name' => $shelf->name, 'description' => $shelf->description, 'active' => $shelf->active,
            'grades' => $shelf->grade_from === $shelf->grade_to ? ($grades[$shelf->grade_from] ?? '') : ($grades[$shelf->grade_from] ?? '').' – '.($grades[$shelf->grade_to] ?? '')];
    }

    /** @return array<int, string> sort order => grade name */
    private function grades(): array
    {
        return GradeLevel::query()->orderBy('sort_order')->pluck('name', 'sort_order')->map(fn ($n) => (string) $n)->all();
    }

    /** @return array<int, string> */
    private function names(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        return app(CurrentSchool::class)->acrossSchools(fn () => User::query()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($u) => [(int) $u->id => trim($u->first_name.' '.$u->last_name)])->all());
    }
}
