<?php

declare(strict_types=1);

namespace App\Services\Reading;

use App\Models\Reading\ReadingBook;
use App\Models\Reading\ReadingBookText;
use App\Models\Reading\ReadingQuestion;
use App\Models\Reading\ReadingRightsDecision;
use App\Models\Reading\ReadingShelf;
use App\Models\Reading\ReadingShelfBook;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The reading library catalogue (Action Plan 4.4), written by HQ content
 * staff in /saas/reading: books (the fields of a Gutendex record), their
 * rights decisions, shelves by grade and comprehension questions. No school
 * writes here; a school only hides books and shelves for its own learners.
 * engineering/specs/learning/reading-library.md.
 */
class ReadingCatalogue
{
    public const LEVELS = ['beginner' => 'Beginner reader', 'developing' => 'Developing reader', 'confident' => 'Confident reader', 'fluent' => 'Fluent reader'];

    public const DECISIONS = [ReadingBook::CLEARED => 'Public domain in South Africa', 'not_cleared' => 'Not cleared: do not serve'];

    /**
     * South African copyright in a literary work generally runs for the
     * author's life and 50 years from the end of the year they died
     * (Copyright Act 98 of 1978, s 3(2)(a)). Recorded as needing legal
     * confirmation: see the specification.
     */
    public const TERM_YEARS = 50;

    public const MAX_SHELF_BOOKS = 200;

    public const MAX_QUESTIONS = 100;

    public static function bookRules(?int $ignore = null): array
    {
        return [
            'gutenberg_id' => ['required', 'integer', 'min:1', 'max:9999999', Rule::unique('reading_books', 'gutenberg_id')->ignore($ignore)],
            'title' => ['required', 'string', 'min:1', 'max:255'],
            'author' => ['required', 'string', 'min:2', 'max:191'],
            'author_birth_year' => ['nullable', 'integer', 'between:-3000,2100'],
            'author_death_year' => ['nullable', 'integer', 'between:-3000,2100'],
            'language' => ['required', 'string', 'regex:/^[a-z]{2,3}$/'],
            'subjects' => ['nullable', 'string', 'max:1000'],
            'reading_level' => ['required', Rule::in(array_keys(self::LEVELS))],
            'summary' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public static function updateRules(int $id): array
    {
        return self::bookRules($id) + ['revision' => ['required', 'integer', 'min:1']];
    }

    public static function rightsRules(): array
    {
        return ['decision' => ['required', Rule::in(array_keys(self::DECISIONS))], 'author_death_year' => ['nullable', 'integer', 'between:-3000,2100'],
            'basis' => ['required', 'string', 'min:20', 'max:2000'], 'translator_checked' => ['nullable', 'boolean'], 'revision' => ['required', 'integer', 'min:1']];
    }

    public static function shelfRules(): array
    {
        return ['name' => ['required', 'string', 'min:2', 'max:120'], 'description' => ['nullable', 'string', 'max:500'],
            'grade_from' => ['required', 'integer', 'between:0,12'], 'grade_to' => ['required', 'integer', 'between:0,12', 'gte:grade_from'],
            'active' => ['nullable', 'boolean']];
    }

    public static function shelfUpdateRules(): array
    {
        return self::shelfRules() + ['revision' => ['required', 'integer', 'min:1']];
    }

    public static function shelfBookRules(): array
    {
        return ['book_ids' => ['present', 'array', 'max:'.self::MAX_SHELF_BOOKS], 'book_ids.*' => ['integer', 'distinct', 'min:1'], 'revision' => ['required', 'integer', 'min:1']];
    }

    public static function questionRules(): array
    {
        return ['prompt' => ['required', 'string', 'min:5', 'max:500'], 'options' => ['required', 'array', 'min:2', 'max:5'],
            'options.*' => ['required', 'string', 'min:1', 'max:200', 'distinct'], 'answer' => ['required', 'integer', 'min:0', 'max:4'],
            'explanation' => ['nullable', 'string', 'max:1000'], 'after_page' => ['nullable', 'integer', 'min:1', 'max:100000']];
    }

    public function createBook(User $actor, array $input): ReadingBook
    {
        $actor = app(ReadingAccess::class)->hq($actor);
        $data = $this->bookData(Validator::make($input, self::bookRules())->validate());

        return DB::transaction(function () use ($actor, $data): ReadingBook {
            $book = ReadingBook::query()->create($data + ['rights_status' => 'undecided', 'revision' => 1, 'created_by' => $actor->id]);
            Audit::record('reading_book_added', $book, [], ['gutenberg_id' => $book->gutenberg_id, 'changed_by' => $actor->id]);

            return $book;
        });
    }

    public function updateBook(User $actor, int $id, array $input): ReadingBook
    {
        $actor = app(ReadingAccess::class)->hq($actor);
        $data = Validator::make($input, self::updateRules($id))->validate();

        $replaced = false;
        $book = DB::transaction(function () use ($actor, $id, $data, &$replaced): ReadingBook {
            $book = ReadingBook::query()->lockForUpdate()->findOrFail($id);
            abort_unless((int) $data['revision'] === $book->revision, 409, 'This book changed. Reload it and try again.');
            $fields = $this->bookData($data);
            $replaced = $fields['gutenberg_id'] !== $book->gutenberg_id;
            if ($fields['gutenberg_id'] !== $book->gutenberg_id || ($book->rights_status !== 'undecided' && $fields['author_death_year'] !== $book->author_death_year)) {
                // A different text or a different death year needs a new rights decision.
                $fields += ['rights_status' => 'undecided', 'rights_decision_id' => null];
            }
            $before = $book->only(array_keys($fields));
            $book->fill($fields + ['revision' => $book->revision + 1])->save();
            Audit::record('reading_book_changed', $book, $before, $book->only(array_keys($fields)) + ['changed_by' => $actor->id]);

            return $book;
        });
        // The cached text belongs to the old Gutenberg number: drop it, so the next read fetches the new one.
        if ($replaced) {
            app(ReadingFetcher::class)->forget($book);
        }

        return $book;
    }

    /**
     * Record HQ's copyright decision on a book. Only a positive decision lets
     * the book be served. A positive decision needs the basis written down,
     * the translator (and any illustrator or editor whose work is in the text)
     * checked, and, when the author's death year is known, the South African
     * term to have run out.
     */
    public function decideRights(User $actor, int $id, array $input): ReadingRightsDecision
    {
        $actor = app(ReadingAccess::class)->hq($actor);
        $data = Validator::make($input, self::rightsRules())->validate();
        $positive = $data['decision'] === ReadingBook::CLEARED;
        $translator = filter_var($data['translator_checked'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return DB::transaction(function () use ($actor, $id, $data, $positive, $translator): ReadingRightsDecision {
            $book = ReadingBook::query()->lockForUpdate()->findOrFail($id);
            abort_unless((int) $data['revision'] === $book->revision, 409, 'This book changed. Reload it and try again.');
            $death = isset($data['author_death_year']) ? (int) $data['author_death_year'] : $book->author_death_year;
            if ($positive && ! $translator) {
                throw ValidationException::withMessages(['translator_checked' => 'Check the translator, editor and illustrator of this edition before clearing it.']);
            }
            if ($positive && $death !== null && ! self::termEnded($death)) {
                throw ValidationException::withMessages(['author_death_year' => 'South African copyright in this author\'s work has not ended: it runs for 50 years from the end of the year they died.']);
            }
            $decision = ReadingRightsDecision::query()->create(['reading_book_id' => $book->id, 'decision' => $data['decision'], 'author_death_year' => $death,
                'basis' => trim($data['basis']), 'translator_checked' => $translator, 'decided_by' => $actor->id, 'decided_at' => now()]);
            $before = ['rights_status' => $book->rights_status];
            $book->fill(['rights_status' => $data['decision'], 'rights_decision_id' => $decision->id, 'author_death_year' => $death, 'revision' => $book->revision + 1])->save();
            Audit::record('reading_rights_decided', $book, $before, ['rights_status' => $book->rights_status, 'decision_id' => $decision->id, 'changed_by' => $actor->id]);

            return $decision;
        });
    }

    /** Whether the South African term (life + 50 years from the end of the year of death) has run out this year. */
    public static function termEnded(int $deathYear, ?CarbonImmutable $today = null): bool
    {
        $year = ($today ?? CarbonImmutable::now('Africa/Johannesburg'))->year;

        return $year > $deathYear + self::TERM_YEARS;
    }

    /**
     * Fetch (or fetch again) a book's text now, so HQ can read the original
     * header for the rights check and see the pages before learners do. This
     * also works before a rights decision: fetching is not serving.
     */
    public function fetchNow(User $actor, int $id): ReadingBookText
    {
        $actor = app(ReadingAccess::class)->hq($actor);
        $book = ReadingBook::query()->findOrFail($id);
        try {
            $text = app(ReadingFetcher::class)->ensure($book, true);
        } catch (ReadingFetchFailed $failure) {
            Audit::record('reading_text_fetch_failed', $book, [], ['reason' => $failure->reason, 'changed_by' => $actor->id]);
            throw ValidationException::withMessages(['fetch' => $failure->getMessage()]);
        }
        Audit::record('reading_text_fetched', $book, [], ['sha256' => $text->sha256, 'pages' => $text->page_count, 'changed_by' => $actor->id]);

        return $text;
    }

    public function createShelf(User $actor, array $input): ReadingShelf
    {
        $actor = app(ReadingAccess::class)->hq($actor);
        $data = Validator::make($input, self::shelfRules())->validate();

        return DB::transaction(function () use ($actor, $data): ReadingShelf {
            $shelf = ReadingShelf::query()->create($this->shelfData($data) + ['revision' => 1, 'created_by' => $actor->id]);
            Audit::record('reading_shelf_added', $shelf, [], ['grades' => [$shelf->grade_from, $shelf->grade_to], 'changed_by' => $actor->id]);

            return $shelf;
        });
    }

    public function updateShelf(User $actor, int $id, array $input): ReadingShelf
    {
        $actor = app(ReadingAccess::class)->hq($actor);
        $data = Validator::make($input, self::shelfUpdateRules())->validate();

        return DB::transaction(function () use ($actor, $id, $data): ReadingShelf {
            $shelf = ReadingShelf::query()->lockForUpdate()->findOrFail($id);
            abort_unless((int) $data['revision'] === $shelf->revision, 409, 'This shelf changed. Reload it and try again.');
            $fields = $this->shelfData($data);
            $before = $shelf->only(array_keys($fields));
            $shelf->fill($fields + ['revision' => $shelf->revision + 1])->save();
            Audit::record('reading_shelf_changed', $shelf, $before, $shelf->only(array_keys($fields)) + ['changed_by' => $actor->id]);

            return $shelf;
        });
    }

    /** Replace the books on a shelf, in the order given. */
    public function setShelfBooks(User $actor, int $id, array $input): ReadingShelf
    {
        $actor = app(ReadingAccess::class)->hq($actor);
        $data = Validator::make($input, self::shelfBookRules())->validate();
        $ids = array_map('intval', $data['book_ids']);

        return DB::transaction(function () use ($actor, $id, $data, $ids): ReadingShelf {
            $shelf = ReadingShelf::query()->lockForUpdate()->findOrFail($id);
            abort_unless((int) $data['revision'] === $shelf->revision, 409, 'This shelf changed. Reload it and try again.');
            if (ReadingBook::query()->whereIn('id', $ids)->count() !== count($ids)) {
                throw ValidationException::withMessages(['book_ids' => 'Choose books from the catalogue.']);
            }
            $before = ReadingShelfBook::query()->where('reading_shelf_id', $shelf->id)->orderBy('position')->pluck('reading_book_id')->map(fn ($b) => (int) $b)->all();
            ReadingShelfBook::query()->where('reading_shelf_id', $shelf->id)->get()->each->delete();
            foreach (array_values($ids) as $position => $book) {
                ReadingShelfBook::query()->create(['reading_shelf_id' => $shelf->id, 'reading_book_id' => $book, 'position' => $position + 1]);
            }
            $shelf->fill(['revision' => $shelf->revision + 1])->save();
            Audit::record('reading_shelf_books_changed', $shelf, ['book_ids' => $before], ['book_ids' => $ids, 'changed_by' => $actor->id]);

            return $shelf;
        });
    }

    public function addQuestion(User $actor, int $bookId, array $input): ReadingQuestion
    {
        $actor = app(ReadingAccess::class)->hq($actor);
        $data = Validator::make($input, self::questionRules())->validate();
        $options = array_values(array_map(fn ($o) => trim((string) $o), $data['options']));
        if ((int) $data['answer'] >= count($options)) {
            throw ValidationException::withMessages(['answer' => 'Choose which of the options is right.']);
        }

        return DB::transaction(function () use ($actor, $bookId, $data, $options): ReadingQuestion {
            $book = ReadingBook::query()->lockForUpdate()->findOrFail($bookId);
            if (ReadingQuestion::query()->where('reading_book_id', $book->id)->where('active', true)->count() >= self::MAX_QUESTIONS) {
                throw ValidationException::withMessages(['prompt' => 'A book has at most 100 questions.']);
            }
            $question = ReadingQuestion::query()->create(['reading_book_id' => $book->id, 'after_page' => isset($data['after_page']) ? (int) $data['after_page'] : null,
                'prompt' => trim($data['prompt']), 'options' => $options, 'answer' => (int) $data['answer'],
                'explanation' => isset($data['explanation']) ? trim($data['explanation']) : null, 'active' => true, 'created_by' => $actor->id]);
            Audit::record('reading_question_added', $question, [], ['reading_book_id' => $book->id, 'changed_by' => $actor->id]);

            return $question;
        });
    }

    /** A retired question is no longer asked; answers already given stay. */
    public function retireQuestion(User $actor, int $id): ReadingQuestion
    {
        $actor = app(ReadingAccess::class)->hq($actor);

        return DB::transaction(function () use ($actor, $id): ReadingQuestion {
            $question = ReadingQuestion::query()->lockForUpdate()->findOrFail($id);
            if ($question->active) {
                $question->fill(['active' => false])->save();
                Audit::record('reading_question_retired', $question, ['active' => true], ['active' => false, 'changed_by' => $actor->id]);
            }

            return $question;
        });
    }

    private function bookData(array $data): array
    {
        $subjects = array_values(array_unique(array_filter(array_map(fn ($s) => mb_substr(trim($s), 0, 120), preg_split('/[\n;]+/', (string) ($data['subjects'] ?? '')) ?: []), fn ($s) => $s !== '')));

        return ['gutenberg_id' => (int) $data['gutenberg_id'], 'title' => trim($data['title']), 'author' => trim($data['author']),
            'author_birth_year' => isset($data['author_birth_year']) ? (int) $data['author_birth_year'] : null,
            'author_death_year' => isset($data['author_death_year']) ? (int) $data['author_death_year'] : null,
            'language' => $data['language'], 'subjects' => array_slice($subjects, 0, 20), 'reading_level' => $data['reading_level'],
            'summary' => isset($data['summary']) && trim($data['summary']) !== '' ? trim($data['summary']) : null];
    }

    private function shelfData(array $data): array
    {
        return ['name' => trim($data['name']), 'description' => isset($data['description']) && trim($data['description']) !== '' ? trim($data['description']) : null,
            'grade_from' => (int) $data['grade_from'], 'grade_to' => (int) $data['grade_to'], 'active' => filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN)];
    }
}
