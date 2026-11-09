<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Academics\GradeLevel;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeSetEntry;
use App\Models\Library\LibraryFramework;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryRating;
use App\Models\Library\LibrarySource;
use App\Models\Library\LibraryTopic;
use App\Models\User;
use App\Services\Learning\PracticeAnswers;
use App\Services\Learning\PracticeBank;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Teachers browse, copy and rate questions other teachers shared (Action Plan
 * 5.4), at a school whose library switch is on. Only published items shared
 * by schools are offered, never a draft, a version waiting for review, a
 * withdrawn or unpublished item, or Navigate Ed's own licensed content (whose
 * licences may not allow copying). A copy is the school's own question,
 * approved by the teacher for one of their groups exactly as any question
 * is. Ratings are the teacher's own; other schools see only an average and a
 * count from the threshold up (LibraryRatings), and never who rated.
 */
class SharedLibraryBrowse
{
    public const PER_PAGE = 25;

    public static function filterRules(): array
    {
        return ['framework' => ['nullable', 'string', 'max:16'], 'subject_code' => ['nullable', 'string', 'max:32'], 'grade_level_id' => ['nullable', 'integer', 'min:1'],
            'library_topic_id' => ['nullable', 'integer', 'min:1'], 'page' => ['nullable', 'integer', 'min:1', 'max:10000']];
    }

    public static function rateRules(): array
    {
        return ['stars' => ['required', 'integer', 'between:1,5'], 'comment' => ['nullable', 'string', 'max:'.(int) config('shared_library.comment_max', 500)]];
    }

    public static function copyRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'group_id' => ['required', 'integer', 'min:1'], 'topic_id' => ['required', 'integer', 'min:1'],
            'content_version_id' => ['required', 'integer', 'min:1']];
    }

    /** Whether the current school's library switch is on (HQ sets it). */
    public function open(): bool
    {
        return app(LibrarySchools::class)->enabled();
    }

    /**
     * Published shared items, newest first, by framework, subject, grade and topic.
     *
     * @return array{items: list<array<string, mixed>>, page: int, last_page: int, total: int, filters: array<string, mixed>}
     */
    public function catalogue(User $actor, array $input): array
    {
        $actor = $this->teacher($actor);
        $data = Validator::make($input, self::filterRules())->validate();
        $paginator = $this->offered()->with('topic.framework', 'source')
            ->whereHas('topic', function ($topic) use ($data) {
                $topic->when($data['framework'] ?? null, fn ($q, $code) => $q->whereHas('framework', fn ($f) => $f->where('code', $code)))
                    ->when($data['subject_code'] ?? null, fn ($q, $code) => $q->where('subject_code', $code))
                    ->when($data['grade_level_id'] ?? null, fn ($q, $grade) => $q->where('grade_level_id', (int) $grade))
                    ->when($data['library_topic_id'] ?? null, fn ($q, $id) => $q->whereKey((int) $id));
            })
            ->orderByDesc('id')->paginate(self::PER_PAGE, ['*'], 'page', (int) ($data['page'] ?? 1));
        $items = collect($paginator->items());
        $ids = $items->pluck('id')->map(fn ($id) => (int) $id)->all();
        $ratings = app(LibraryRatings::class)->shown($ids);
        $copied = $this->copied($ids);
        $grades = GradeLevel::query()->pluck('name', 'id');

        return [
            'items' => $items->map(fn (LibraryItem $item) => $this->summary($item, $grades->all(), $ratings[(int) $item->id] ?? null, in_array((int) $item->id, $copied, true)))->values()->all(),
            'page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'total' => $paginator->total(),
            'filters' => $this->filters(),
        ];
    }

    /** One shared item: its question, attribution, licence, ratings (from the threshold up) and the teacher's own rating. */
    public function item(User $actor, int $id): array
    {
        $actor = $this->teacher($actor);
        $item = $this->offered()->with('topic.framework', 'source')->findOrFail($id);
        $version = LibraryItemVersion::query()->where('state', 'published')->findOrFail($item->published_version_id);
        $rating = app(LibraryRatings::class)->shown([(int) $item->id])[(int) $item->id];
        $mine = LibraryRating::query()->where('library_item_id', $item->id)->where('user_id', $actor->id)->first();
        $definition = (array) $version->practiceDefinition();

        return $this->summary($item, GradeLevel::query()->pluck('name', 'id')->all(), $rating, $this->copied([(int) $item->id]) !== []) + [
            'version_id' => (int) $version->id,
            'question' => ['kind' => (string) ($definition['kind'] ?? ''), 'prompt' => (string) ($definition['prompt'] ?? ''), 'options' => array_values((array) ($definition['options'] ?? [])),
                'answers' => array_values((array) ($definition['answers'] ?? [])), 'steps' => array_values((array) ($definition['steps'] ?? [])),
                'explanation' => (string) ($definition['explanation'] ?? ''), 'max_seconds' => $definition['max_seconds'] ?? null],
            'estimated_seconds' => (int) $version->estimated_seconds,
            'comments' => $rating === null ? [] : app(LibraryRatings::class)->comments((int) $item->id),
            'my_rating' => $mine === null ? null : ['stars' => (int) $mine->stars, 'comment' => $mine->comment],
            'can_rate' => $this->used($item),
            'own_school' => $this->ownSchool($item),
            'copyable' => in_array($definition['kind'] ?? null, SharedLibrary::SHAREABLE_KINDS, true),
        ];
    }

    /**
     * Rate an item the teacher's school has used (copied into its banks, or
     * practised by its learners), 1 to 5 with an optional short comment,
     * scanned for identifying data. Once per item per teacher; saving again
     * changes it. The sharing school's own ratings are kept but never count.
     *
     * @return array{stars: int, comment: ?string, counted: bool}
     */
    public function rate(User $actor, int $id, array $input): array
    {
        $actor = $this->teacher($actor, true);
        $data = Validator::make($input, self::rateRules())->validate();
        $item = $this->offered()->with('source')->findOrFail($id);
        abort_unless($this->used($item), 403, __('Rate a shared question once your school has used it: copy it into a question bank, or let your learners practise it.'));
        $comment = isset($data['comment']) && trim((string) $data['comment']) !== '' ? trim((string) $data['comment']) : null;
        if ($comment !== null) {
            app(SharedLibraryScan::class)->refuse([$comment], 'comment');
        }

        return DB::transaction(function () use ($actor, $item, $data, $comment) {
            $row = LibraryRating::query()->where('library_item_id', $item->id)->where('user_id', $actor->id)->lockForUpdate()->first();
            $before = $row === null ? [] : ['stars' => (int) $row->stars];
            if ($row === null) {
                try {
                    $row = DB::transaction(fn () => LibraryRating::query()->create(['library_item_id' => $item->id, 'user_id' => $actor->id, 'stars' => (int) $data['stars'], 'comment' => $comment]));
                } catch (UniqueConstraintViolationException) {
                    // The same teacher rated it while working at another school: once per item per teacher.
                    abort(409, __('You have already rated this question.'));
                }
            } else {
                $row->update(['stars' => (int) $data['stars'], 'comment' => $comment]);
            }
            Audit::record('library.rating_saved', $row, $before, ['library_item_id' => (int) $item->id, 'stars' => (int) $row->stars, 'comment' => $comment !== null, 'changed_by' => $actor->id]);

            return ['stars' => (int) $row->stars, 'comment' => $row->comment, 'counted' => ! $this->ownSchool($item)];
        });
    }

    /**
     * Copy a shared question into the school's own bank: the teacher approves
     * it for one of their groups, with a topic and lesson material of that
     * group, through the same checks as any question (PracticeBank). The copy
     * is the school's, remembers the library version it came from, and never
     * changes when the library item does.
     */
    public function copy(User $actor, int $id, array $input): array
    {
        $actor = $this->teacher($actor, true);
        $data = Validator::make($input, self::copyRules())->validate();
        $item = $this->offered()->findOrFail($id);
        $version = LibraryItemVersion::query()->where('state', 'published')->findOrFail($item->published_version_id);
        $definition = (array) $version->practiceDefinition();
        abort_unless(in_array($definition['kind'] ?? null, SharedLibrary::SHAREABLE_KINDS, true), 409, __('Questions with a picture cannot be copied yet.'));
        $result = app(PracticeBank::class)->approve($actor, (int) $data['group_id'], null, ['operation_id' => $data['operation_id'], 'expected_revision' => 0,
            'topic_id' => (int) $data['topic_id'], 'content_version_id' => (int) $data['content_version_id'], 'estimated_seconds' => (int) $version->estimated_seconds,
            'active' => true, 'definition' => app(PracticeAnswers::class)->definition($definition, SharedLibrary::SHAREABLE_KINDS)], (int) $version->id);
        Audit::record('library.item_copied', PracticeItem::query()->find($result['id']), [], ['library_item_id' => (int) $item->id, 'library_version_id' => (int) $version->id,
            'practice_item_id' => (int) $result['id'], 'changed_by' => $actor->id]);

        return $result + ['library_item_id' => (int) $item->id];
    }

    /**
     * How a published item credits who shared it, as they chose: their name and
     * school, the school only, or no one in particular.
     */
    public function attribution(LibrarySource $source): string
    {
        // Schools and people are platform rows: only the two names the teacher chose to show are read.
        $school = $source->contributor_school_id === null ? null : DB::table('schools')->where('id', $source->contributor_school_id)->value('name');
        if ($source->credit === 'named' && $school !== null) {
            $user = DB::table('users')->where('id', $source->contributor_user_id)->first(['first_name', 'last_name']);
            $name = $user === null ? '' : trim($user->first_name.' '.$user->last_name);
            if ($name !== '') {
                return __('Shared by :teacher, :school', ['teacher' => $name, 'school' => $school]);
            }
        }
        if (in_array($source->credit, ['named', 'school'], true) && $school !== null) {
            return __('Shared by :school', ['school' => $school]);
        }

        return __('Shared by a NavigateEd teacher');
    }

    /** The choices the browse filters offer: only what shared items use. */
    public function filters(): array
    {
        $topics = LibraryTopic::query()->with('framework')->whereIn('id', $this->offered()->select('library_topic_id'))->orderBy('subject_code')->orderBy('name')->limit(1000)->get();
        $grades = GradeLevel::query()->whereIn('id', $topics->pluck('grade_level_id')->unique())->orderBy('sort_order')->pluck('name', 'id');

        return [
            'frameworks' => LibraryFramework::query()->whereIn('id', $topics->pluck('library_framework_id')->unique())->orderBy('code')->pluck('code')->values()->all(),
            'subject_codes' => $topics->pluck('subject_code')->unique()->sort()->values()->all(),
            'grades' => $grades->map(fn ($name, $id) => ['id' => (int) $id, 'name' => (string) $name])->values()->all(),
            'topics' => $topics->map(fn (LibraryTopic $t) => ['id' => (int) $t->id, 'name' => $t->name, 'framework' => (string) $t->framework?->code, 'subject_code' => $t->subject_code,
                'grade' => (string) $grades->get($t->grade_level_id)])->values()->all(),
        ];
    }

    /** Published items shared by schools: the only library items this page offers. */
    private function offered(): Builder
    {
        return LibraryItem::query()->where('kind', 'practice_question')->whereNotNull('published_version_id')->whereNull('withdrawn_at')
            ->whereHas('source', fn ($s) => $s->where('kind', 'contributed'));
    }

    private function teacher(User $actor, bool $write = false): User
    {
        $actor = app(SharedLibraryAccess::class)->teacher($actor, $write);
        abort_unless($this->open(), 403, __('The shared library is not switched on for your school yet.'));

        return $actor;
    }

    /** @param  array<int, string>  $grades */
    private function summary(LibraryItem $item, array $grades, ?array $rating, bool $copied): array
    {
        return ['id' => (int) $item->id, 'title' => $item->title, 'topic' => ['id' => (int) $item->topic->id, 'name' => $item->topic->name, 'framework' => (string) $item->topic->framework?->code,
            'subject_code' => $item->topic->subject_code, 'grade' => (string) ($grades[$item->topic->grade_level_id] ?? '')],
            'attribution' => $this->attribution($item->source), 'licence' => ['code' => $item->source->licence, 'url' => $item->source->url],
            'rating' => $rating, 'copied' => $copied];
    }

    /**
     * Items of these that the current school holds a copy of.
     *
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function copied(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return LibraryItemVersion::query()->whereIn('library_item_id', $ids)
            ->whereIn('id', PracticeItem::query()->whereNotNull('library_item_version_id')->select('library_item_version_id'))
            ->distinct()->pluck('library_item_id')->map(fn ($id) => (int) $id)->all();
    }

    /** The school shared it, holds a copy of it, or its learners met it in daily practice. */
    private function used(LibraryItem $item): bool
    {
        if ($this->ownSchool($item)) {
            return true;
        }
        $versions = LibraryItemVersion::query()->where('library_item_id', $item->id)->select('id');

        return PracticeItem::query()->whereIn('library_item_version_id', $versions)->exists()
            || PracticeSetEntry::query()->whereIn('library_item_version_id', LibraryItemVersion::query()->where('library_item_id', $item->id)->select('id'))->exists();
    }

    private function ownSchool(LibraryItem $item): bool
    {
        return (int) $item->source->contributor_school_id === app(CurrentSchool::class)->id();
    }
}
