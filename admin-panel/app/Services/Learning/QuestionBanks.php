<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\ContentVersion;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeItemGroup;
use App\Models\Learning\PracticeItemVersion;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Teachers' question banks (Action Plan 3.6). A practice question belongs to a
 * subject and grade in its school, not to one group, so it survives the year
 * change. Its author chooses who may find and reuse it; it reaches the
 * learners of each group it is used in. Only the author or a subject lead
 * changes it; colleagues may only use it. Never across schools: every query
 * runs inside the current school. engineering/specs/learning/question-banks.md.
 */
class QuestionBanks
{
    /** @var array<string, bool> */
    private array $topics = [];

    /**
     * Subject leads, for this step: the people who already oversee every
     * teacher's learning content in a grade (academic managers, the grade's
     * head and the principal; ContentPolicy::leads).
     */
    public function leads(User $actor, int $gradeId): bool
    {
        return app(ContentPolicy::class)->leads($actor, $gradeId);
    }

    public function canEdit(User $actor, PracticeItem $item): bool
    {
        return ($item->author_id !== null && (int) $item->author_id === (int) $actor->id) || $this->leads($actor, (int) $item->grade_level_id);
    }

    /** May the actor put this question into one of their own groups? */
    public function canUse(User $actor, PracticeItem $item): bool
    {
        if (! $item->active) {
            return false;
        }
        if ($item->author_id !== null && (int) $item->author_id === (int) $actor->id) {
            return true;
        }
        $taught = $this->taught($actor)->where('subject_id', $item->subject_id)->where('grade_level_id', $item->grade_level_id)->pluck('id');

        return match ($item->visibility) {
            'school' => $taught->isNotEmpty(),
            'groups' => PracticeItemGroup::query()->where('practice_item_id', $item->id)->whereIn('teaching_group_id', $taught)->exists(),
            default => false,
        };
    }

    /**
     * The subjects and grades whose banks the actor may open: those of the
     * groups they teach this year, their own questions' (so a bank outlives
     * its groups), and every bank in a grade they lead.
     *
     * @return list<array{subject_id: int, grade_level_id: int, label: string}>
     */
    public function scopes(User $actor): array
    {
        $fresh = app(PracticeAccess::class)->actor($actor);
        app(AcademicAuthority::class)->staff($fresh);
        $pairs = $this->taught($fresh)->map(fn ($g) => [(int) $g->subject_id, (int) $g->grade_level_id])->all();
        foreach (PracticeItem::query()->where('author_id', $fresh->id)->distinct()->get(['subject_id', 'grade_level_id']) as $row) {
            $pairs[] = [(int) $row->subject_id, (int) $row->grade_level_id];
        }
        $year = (int) schoolSetting('session_year');
        $grades = TeachingGroup::query()->where('session_year_id', $year)->where('active', true)->distinct()->pluck('grade_level_id')
            ->merge(PracticeItem::query()->distinct()->pluck('grade_level_id'))->map(fn ($id) => (int) $id)->unique();
        foreach ($grades->filter(fn ($grade) => $this->leads($fresh, $grade)) as $grade) {
            foreach (TeachingGroup::query()->where('session_year_id', $year)->where('active', true)->where('grade_level_id', $grade)->distinct()->get(['subject_id']) as $row) {
                $pairs[] = [(int) $row->subject_id, $grade];
            }
            foreach (PracticeItem::query()->where('grade_level_id', $grade)->distinct()->get(['subject_id']) as $row) {
                $pairs[] = [(int) $row->subject_id, $grade];
            }
        }
        $pairs = collect($pairs)->unique(fn ($pair) => implode('|', $pair));
        $subjects = Subject::query()->whereIn('id', $pairs->pluck(0))->pluck('name', 'id');
        $gradeRows = GradeLevel::query()->whereIn('id', $pairs->pluck(1))->get(['id', 'name', 'sort_order'])->keyBy('id');

        return $pairs->filter(fn ($pair) => $subjects->has($pair[0]) && $gradeRows->has($pair[1]))
            ->sortBy(fn ($pair) => sprintf('%03d|%s', $gradeRows[$pair[1]]->sort_order, $subjects[$pair[0]]))
            ->map(fn ($pair) => ['subject_id' => $pair[0], 'grade_level_id' => $pair[1], 'label' => __(':subject, :grade', ['subject' => $subjects[$pair[0]], 'grade' => $gradeRows[$pair[1]]->name])])
            ->values()->all();
    }

    /**
     * One subject-and-grade bank as the actor may see it: their own questions,
     * the questions used in groups they teach, questions shared with the
     * school's subject-and-grade colleagues (while they teach that subject and
     * grade), and, for a subject lead, every question in the bank.
     *
     * @return array{items: list<array<string, mixed>>, page: int, last_page: int, total: int}
     */
    public function bank(User $actor, int $subjectId, int $gradeId, int $page = 1): array
    {
        $fresh = app(PracticeAccess::class)->actor($actor);
        app(AcademicAuthority::class)->staff($fresh);
        abort_unless(collect($this->scopes($fresh))->contains(fn ($s) => $s['subject_id'] === $subjectId && $s['grade_level_id'] === $gradeId), 404);
        $year = (int) schoolSetting('session_year');
        $mine = $this->taught($fresh)->where('subject_id', $subjectId)->where('grade_level_id', $gradeId)->values();
        $items = $this->visible($fresh, $subjectId, $gradeId)->orderByDesc('active')->orderByDesc('id')->paginate(25, ['*'], 'page', max(1, $page));
        $versions = PracticeItemVersion::query()->whereIn('id', PracticeItemVersion::query()->selectRaw('MAX(id)')->whereIn('practice_item_id', $items->pluck('id'))->groupBy('practice_item_id'))
            ->get()->keyBy('practice_item_id');
        $topics = MasteryTopic::query()->whereIn('id', $items->pluck('topic_id'))->pluck('name', 'id');
        $authors = User::query()->whereIn('id', $items->pluck('author_id')->filter())->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $uses = PracticeItemGroup::query()->whereIn('practice_item_id', $items->pluck('id'))->get()->groupBy('practice_item_id');
        $groups = TeachingGroup::query()->whereIn('id', $uses->flatten()->pluck('teaching_group_id'))->get(['id', 'name', 'session_year_id', 'subject_id', 'grade_level_id'])->keyBy('id');

        $rows = $items->map(function (PracticeItem $item) use ($fresh, $versions, $topics, $authors, $uses, $groups, $year, $mine) {
            $version = $versions->get($item->id);
            $used = collect($uses->get($item->id, []))->map(fn ($use) => $groups->get($use->teaching_group_id))->filter();
            $current = $used->where('session_year_id', $year)->sortBy('name')->values();
            $canUse = $this->canUse($fresh, $item);

            return [
                'id' => (int) $item->id,
                'prompt' => (string) ($version?->definition['prompt'] ?? ''),
                'kind' => (string) ($version?->definition['kind'] ?? ''),
                'topic' => (string) ($topics[$item->topic_id] ?? ''),
                'author' => $item->author_id === null ? null : ($authors->get($item->author_id)?->full_name),
                'mine' => $item->author_id !== null && (int) $item->author_id === (int) $fresh->id,
                'visibility' => $item->visibility,
                'active' => $item->active,
                'can_edit' => $this->canEdit($fresh, $item),
                'uses' => $current->map(fn ($group) => ['group_id' => (int) $group->id, 'name' => $group->name, 'mine' => $mine->contains('id', $group->id),
                    'waiting' => $this->waiting($item, $version, $group)])->all(),
                'earlier_uses' => $used->where('session_year_id', '!=', $year)->count(),
                'targets' => $canUse ? $mine->reject(fn ($group) => $current->contains('id', $group->id))->map(fn ($group) => ['group_id' => (int) $group->id, 'name' => $group->name])->values()->all() : [],
            ];
        })->all();

        return ['items' => $rows, 'page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()];
    }

    /** Use a question in one of the actor's own groups of its subject and grade. */
    public function use(User $actor, int $itemId, int $groupId): void
    {
        DB::transaction(function () use ($actor, $itemId, $groupId) {
            $group = app(PracticeAccess::class)->staff($actor, $groupId, true);
            $item = PracticeItem::query()->lockForUpdate()->findOrFail($itemId);
            abort_unless($this->visibleTo($actor, $item), 404);
            abort_unless((int) $item->subject_id === (int) $group->subject_id && (int) $item->grade_level_id === (int) $group->grade_level_id, 422, __('Use a question only in a group of its own subject and grade.'));
            abort_unless($item->active, 422, __('Restore the question before using it.'));
            abort_unless($this->canUse($actor, $item), 403, __('The author keeps this question to themselves.'));
            $use = PracticeItemGroup::query()->firstOrCreate(['practice_item_id' => $item->id, 'teaching_group_id' => $group->id], ['added_by' => $actor->id]);
            if ($use->wasRecentlyCreated) {
                Audit::record('learning.practice_used', $item, [], ['teaching_group_id' => (int) $group->id]);
            }
        });
    }

    /** Stop using a question in one of the actor's groups. The question stays in its bank. */
    public function stop(User $actor, int $itemId, int $groupId): void
    {
        DB::transaction(function () use ($actor, $itemId, $groupId) {
            $group = app(PracticeAccess::class)->staff($actor, $groupId, true);
            $item = PracticeItem::query()->lockForUpdate()->findOrFail($itemId);
            $use = PracticeItemGroup::query()->where('practice_item_id', $item->id)->where('teaching_group_id', $group->id)->lockForUpdate()->firstOrFail();
            $use->delete();
            Audit::record('learning.practice_unused', $item, ['teaching_group_id' => (int) $group->id], []);
        });
    }

    /**
     * The author or a subject lead changes who may find and reuse a question,
     * or archives and restores it (an archived question reaches no learner).
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $actor, int $itemId, array $input): void
    {
        $data = Validator::make($input, self::rules())->validate();
        DB::transaction(function () use ($actor, $itemId, $data) {
            $fresh = app(PracticeAccess::class)->actor($actor, true);
            app(AcademicAuthority::class)->staff($fresh);
            $item = PracticeItem::query()->lockForUpdate()->findOrFail($itemId);
            abort_unless($this->visibleTo($fresh, $item), 404);
            abort_unless($this->canEdit($fresh, $item), 403, __('Only the question\'s author or a subject lead may change it.'));
            $before = ['visibility' => $item->visibility, 'active' => $item->active];
            // The revision belongs to the approved wording: sharing and archiving never change it.
            $item->forceFill(['visibility' => $data['visibility'], 'active' => (bool) $data['active']])->save();
            Audit::record('learning.practice_shared', $item, $before, ['visibility' => $item->visibility, 'active' => $item->active]);
        });
    }

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return ['visibility' => ['required', 'string', Rule::in(PracticeItem::VISIBILITIES)], 'active' => ['required', 'boolean']];
    }

    public function visibleTo(User $actor, PracticeItem $item): bool
    {
        return $this->visible($actor, (int) $item->subject_id, (int) $item->grade_level_id)->whereKey($item->id)->exists();
    }

    /** @return Builder<PracticeItem> */
    private function visible(User $actor, int $subjectId, int $gradeId): Builder
    {
        $query = PracticeItem::query()->where('subject_id', $subjectId)->where('grade_level_id', $gradeId);
        if ($this->leads($actor, $gradeId)) {
            return $query;
        }
        $taught = $this->taught($actor)->where('subject_id', $subjectId)->where('grade_level_id', $gradeId)->pluck('id')->all();

        return $query->where(function ($scope) use ($actor, $taught) {
            $scope->where('author_id', $actor->id)
                ->orWhereIn('id', PracticeItemGroup::query()->whereIn('teaching_group_id', $taught)->select('practice_item_id'));
            if ($taught !== []) {
                $scope->orWhere('visibility', 'school');
            }
        });
    }

    /**
     * The current year's active groups the actor teaches today.
     *
     * @return Collection<int, TeachingGroup>
     */
    private function taught(User $actor): Collection
    {
        $authority = app(AcademicAuthority::class);
        $teachers = $authority->teachers($actor);
        if ($teachers === []) {
            return collect();
        }
        $today = $authority->today();

        return TeachingGroup::query()->where('session_year_id', (int) schoolSetting('session_year'))->where('active', true)
            ->whereHas('allocations', fn ($q) => $q->whereIn('teacher_id', $teachers)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today))
            ->orderBy('name')->get(['id', 'name', 'subject_id', 'grade_level_id', 'session_year_id']);
    }

    /**
     * Why a question used in a group does not reach its learners yet, or null
     * when it does: its source material must be published to that group, and
     * its topic must be in the group's published curriculum plan. After the
     * year changes, both usually wait for this year's material and plans.
     */
    private function waiting(PracticeItem $item, ?PracticeItemVersion $version, TeachingGroup $group): ?string
    {
        if (! $item->active) {
            return 'archived';
        }
        $source = $version !== null && ContentVersion::query()->whereKey($version->content_version_id)->where('state', 'published')
            ->whereHas('item', fn ($q) => $q->where('active', true)->whereColumn('content_items.published_version_id', 'content_versions.id')
                ->whereHas('audienceGroups', fn ($g) => $g->where('teaching_group_id', $group->id)))->exists();
        if (! $source) {
            return 'source';
        }
        $key = $group->id.'|'.$item->topic_id;
        if (! array_key_exists($key, $this->topics)) {
            try {
                app(PracticeAccess::class)->topic($group, (int) $item->topic_id);
                $this->topics[$key] = true;
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                $this->topics[$key] = false;
            }
        }

        return $this->topics[$key] ? null : 'topic';
    }
}
