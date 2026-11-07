<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\LearningPathItem;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeItemVersion;
use App\Models\Learning\PracticeMemory;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryTopic;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\MarkDefinitions;
use App\Services\Growth\TopicGraph;
use App\Services\Learning\Library\LibraryPractice;
use App\Services\Learning\Library\LibrarySchools;
use Illuminate\Support\Collection;

/**
 * What a guided learning path may teach and ask (Action Plan 3.9): the
 * group's current published topics, the school's own approved questions and
 * material for a topic first, then the platform library's where the school's
 * library switch is on. Every read checks the sources afresh; nothing here
 * writes, and no approved answer leaves this class except to PracticeAnswers.
 */
class LearningPathSources
{
    /**
     * Topics the group teaches now: published curriculum topics of its grade in
     * its current assessment plans, as TutorTopics, without syncing the graph.
     *
     * @return Collection<int, MasteryTopic> keyed by id
     */
    public function teachable(TeachingGroup $group): Collection
    {
        $plans = app(MarkDefinitions::class)->plans($group);
        $nodes = app(TopicGraph::class)->nodes((int) $group->subject_id)
            ->filter(fn ($node) => (int) $node->grade_level_id === (int) $group->grade_level_id && $node->source !== null
                && $plans->contains(fn ($p) => (int) $p->curriculum_plan_id === (int) $node->source->curriculum_plan_id && (int) $p->term_id === (int) $node->source->term_id));
        abort_if($nodes->count() > 200, 422, 'The published topic catalogue exceeds its bounded size.');

        return $nodes->keyBy('id');
    }

    /**
     * Library topics standing for a school topic: the group's frameworks,
     * subject code and grade, with the same name. A school topic has no
     * explicit link to the library yet, so the name is the match.
     *
     * @return list<int>
     */
    public function libraryTopics(TeachingGroup $group, MasteryTopic $topic): array
    {
        if (! app(LibrarySchools::class)->enabled()) {
            return [];
        }
        $scopes = app(LibraryPractice::class)->scopes($group);
        if ($scopes === []) {
            return [];
        }

        return LibraryTopic::query()->where('grade_level_id', $group->grade_level_id)->where('name', trim((string) $topic->name))->where(function ($query) use ($scopes) {
            foreach ($scopes as [$framework, $code]) {
                $query->orWhere(fn ($q) => $q->where('subject_code', $code)->whereHas('framework', fn ($f) => $f->where('code', $framework)));
            }
        })->orderBy('id')->limit(20)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Topic ids of the group that have at least one approved question: the
     * school's own, or a published library question under a same-named topic.
     *
     * @return list<int>
     */
    public function practisable(TeachingGroup $group, Collection $topics): array
    {
        $own = PracticeItem::usedIn((int) $group->id)->where('active', true)->whereIn('topic_id', $topics->keys())->distinct()->pluck('topic_id')->map(fn ($id) => (int) $id)->all();
        $ids = array_fill_keys($own, true);
        $scopes = app(LibrarySchools::class)->enabled() ? app(LibraryPractice::class)->scopes($group) : [];
        if ($scopes !== []) {
            // One query for the names of the group's library topics that have a published question.
            $names = LibraryTopic::query()->where('grade_level_id', $group->grade_level_id)->where(function ($query) use ($scopes) {
                foreach ($scopes as [$framework, $code]) {
                    $query->orWhere(fn ($q) => $q->where('subject_code', $code)->whereHas('framework', fn ($f) => $f->where('code', $framework)));
                }
            })->whereIn('id', LibraryItem::query()->where('kind', 'practice_question')->whereNotNull('published_version_id')->select('library_topic_id'))->limit(5001)->pluck('name')
                ->map(fn ($name) => mb_strtolower(trim((string) $name)))->flip();
            foreach ($topics as $topic) {
                if ($names->has(mb_strtolower(trim((string) $topic->name)))) {
                    $ids[(int) $topic->id] = true;
                }
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /**
     * Approved questions for a topic, the school's first, each list ordered
     * due-for-review first. Keys are stable per question across versions.
     *
     * @return list<array{key: string, version: PracticeItemVersion|LibraryItemVersion}>
     */
    public function candidates(User $actor, int $learner, TeachingGroup $group, MasteryTopic $topic): array
    {
        $items = PracticeItem::usedIn((int) $group->id)->where('topic_id', $topic->id)->where('active', true)->orderBy('id')->limit(201)->get();
        abort_if($items->count() > 200, 422, 'The topic practice bank exceeds its bounded catalogue.');
        $versions = PracticeItemVersion::whereIn('id', PracticeItemVersion::selectRaw('MAX(id)')->whereIn('practice_item_id', $items->pluck('id'))->groupBy('practice_item_id'))->orderBy('practice_item_id')->get();
        $readable = app(ContentPolicy::class)->readableVersions($actor, $learner)->whereIn('content_versions.id', $versions->pluck('content_version_id'))->pluck('content_versions.id')->map(fn ($id) => (int) $id)->all();
        $today = app(AcademicAuthority::class)->today();
        $memories = PracticeMemory::where('learner_id', $learner)->whereIn('practice_item_id', $items->pluck('id'))->get()->keyBy('practice_item_id');
        $own = [];
        foreach ($versions as $version) {
            if (in_array((int) $version->content_version_id, $readable, true)) {
                $memory = $memories->get($version->practice_item_id);
                $own[] = ['key' => 's:'.$version->practice_item_id, 'version' => $version, 'due' => $memory === null || $memory->due_on->toDateString() <= $today ? 0 : 1];
            }
        }
        $library = [];
        $topicIds = $this->libraryTopics($group, $topic);
        if ($topicIds !== []) {
            $libraryItems = LibraryItem::query()->whereIn('library_topic_id', $topicIds)->where('kind', 'practice_question')->whereNotNull('published_version_id')->orderBy('id')->limit(201)->get();
            abort_if($libraryItems->count() > 200, 422, 'The library topic bank exceeds its bounded catalogue.');
            $published = LibraryItemVersion::query()->whereIn('id', $libraryItems->pluck('published_version_id'))->where('state', 'published')->get()->keyBy('id');
            $memories = PracticeMemory::where('learner_id', $learner)->whereIn('library_item_id', $libraryItems->pluck('id'))->get()->keyBy('library_item_id');
            foreach ($libraryItems as $item) {
                $version = $published->get($item->published_version_id);
                if ($version !== null && $version->practiceDefinition() !== null) {
                    $memory = $memories->get($item->id);
                    $library[] = ['key' => 'l:'.$item->id, 'version' => $version, 'due' => $memory === null || $memory->due_on->toDateString() <= $today ? 0 : 1];
                }
            }
        }
        $order = fn ($a, $b) => $a['due'] <=> $b['due'];
        usort($own, $order);
        usort($library, $order);

        return array_map(fn ($row) => ['key' => $row['key'], 'version' => $row['version']], [...$own, ...$library]);
    }

    /** The stable question key of an issued path item, without loading its definition. */
    public function key(LearningPathItem $row): string
    {
        if ($row->library_item_version_id !== null) {
            return 'l:'.(int) LibraryItemVersion::query()->whereKey($row->library_item_version_id)->value('library_item_id');
        }

        return 's:'.(int) PracticeItemVersion::query()->whereKey($row->practice_item_version_id)->value('practice_item_id');
    }

    /**
     * One issued item's question, checked afresh: the school question is still
     * active in the group under this topic and its material is readable to the
     * learner; a library question is still published under a same-named
     * library topic while the school's switch is on. Null when withdrawn.
     *
     * @return array{definition: array, memory: array<string, int>, library: bool}|null
     */
    public function question(User $actor, int $learner, TeachingGroup $group, MasteryTopic $topic, LearningPathItem $row): ?array
    {
        if ($row->library_item_version_id !== null) {
            $topicIds = $this->libraryTopics($group, $topic);
            $version = LibraryItemVersion::query()->where('state', 'published')->find($row->library_item_version_id);
            $item = $version === null ? null : LibraryItem::query()->where('kind', 'practice_question')->whereNotNull('published_version_id')->whereIn('library_topic_id', $topicIds)->find($version->library_item_id);
            if ($item === null || $version->practiceDefinition() === null) {
                return null;
            }

            return ['definition' => (array) $version->practiceDefinition(), 'memory' => ['library_item_id' => (int) $item->id], 'library' => true];
        }
        $meta = PracticeItemVersion::select('id', 'practice_item_id', 'content_version_id')->find($row->practice_item_version_id);
        $item = $meta === null ? null : PracticeItem::usedIn((int) $group->id)->where('topic_id', $topic->id)->where('active', true)->find($meta->practice_item_id);
        if ($item === null || ! app(ContentPolicy::class)->readableVersions($actor, $learner)->whereKey($meta->content_version_id)->exists()) {
            return null;
        }

        return ['definition' => PracticeItemVersion::findOrFail($meta->id)->definition, 'memory' => ['practice_item_id' => (int) $item->id], 'library' => false];
    }

    /**
     * Published material to learn the topic from: the school's own first (the
     * material behind its approved questions on the topic, opened with the
     * academic content reader), then library lessons. At most three.
     *
     * @return list<array<string, mixed>>
     */
    public function lessons(User $actor, int $learner, TeachingGroup $group, MasteryTopic $topic): array
    {
        $lessons = [];
        foreach ($this->schoolMaterial($actor, $learner, $group, $topic) as $id => $title) {
            $lessons[] = ['source' => 'school', 'content_version_id' => $id, 'library_item_version_id' => null, 'title' => $title, 'body' => null];
        }
        $topicIds = count($lessons) < 3 ? $this->libraryTopics($group, $topic) : [];
        if ($topicIds !== []) {
            $items = LibraryItem::query()->whereIn('library_topic_id', $topicIds)->where('kind', 'lesson')->whereNotNull('published_version_id')->orderBy('id')->limit(3 - count($lessons))->get();
            $versions = LibraryItemVersion::query()->whereIn('id', $items->pluck('published_version_id'))->where('state', 'published')->get()->keyBy('id');
            foreach ($items as $item) {
                $version = $versions->get($item->published_version_id);
                if ($version !== null && is_string($version->content['body'] ?? null)) {
                    $lessons[] = ['source' => 'library', 'content_version_id' => null, 'library_item_version_id' => (int) $version->id, 'title' => (string) $item->title, 'body' => $version->content['body']];
                }
            }
        }

        return $lessons;
    }

    /**
     * The school's published material behind its approved questions on the
     * topic that the learner may read, newest first: content version id => title.
     *
     * @return array<int, string>
     */
    public function schoolMaterial(User $actor, int $learner, TeachingGroup $group, MasteryTopic $topic): array
    {
        $items = PracticeItem::usedIn((int) $group->id)->where('topic_id', $topic->id)->where('active', true)->limit(201)->pluck('id');
        $sources = PracticeItemVersion::whereIn('id', PracticeItemVersion::selectRaw('MAX(id)')->whereIn('practice_item_id', $items)->groupBy('practice_item_id'))->pluck('content_version_id');

        return app(ContentPolicy::class)->readableVersions($actor, $learner)->whereIn('content_versions.id', $sources)
            ->whereHas('item', fn ($q) => $q->where('subject_id', $group->subject_id)->where('session_year_id', $group->session_year_id)
                ->whereHas('audienceGroups', fn ($g) => $g->where('teaching_group_id', $group->id)))
            ->orderByDesc('content_versions.id')->limit(3)->get()
            ->mapWithKeys(fn ($version) => [(int) $version->id => (string) ($version->metadata['title'] ?? '')])->all();
    }
}
