<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\PracticeMemory;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryTopic;
use App\Models\Library\LibraryTopicPrerequisite;
use App\Services\Academics\MarkDefinitions;
use App\Services\Growth\TopicGraph;
use Illuminate\Support\Collection;

/**
 * The platform library as a second source of daily practice (Action Plan
 * 3.3), read only inside a school whose library switch is on. A learning
 * group's frameworks come from its published curriculum, exactly as the exam
 * coach reads them (`standard_snapshot.framework`, with the subject `code`),
 * so the library is matched by framework, subject code and grade, never by
 * anything the school wrote. Nothing here writes to a library table.
 */
class LibraryPractice
{
    /** Below this many approved questions of its own, a group's practice is topped up from the library. */
    public const MINIMUM_OWN = 10;

    /**
     * The group's framework and subject code pairs, from its published plans.
     *
     * @return list<array{0: string, 1: string}>
     */
    public function scopes(TeachingGroup $group): array
    {
        $pairs = [];
        foreach (app(MarkDefinitions::class)->plans($group) as $plan) {
            $snapshot = $plan->curriculum->standard_snapshot ?? null;
            if (is_array($snapshot) && is_string($snapshot['framework'] ?? null) && is_string($snapshot['code'] ?? null)) {
                $pairs[$snapshot['framework'].'|'.$snapshot['code']] = [$snapshot['framework'], $snapshot['code']];
            }
        }

        return array_values($pairs);
    }

    /**
     * The school's mirror nodes (TopicGraph::overlay) of the library topics for
     * this group's frameworks, subject and grade, keyed by library topic id.
     *
     * @return Collection<int, MasteryTopic>
     */
    public function topics(TeachingGroup $group): Collection
    {
        if (! app(LibrarySchools::class)->enabled()) {
            return collect();
        }
        $scopes = $this->scopes($group);
        if ($scopes === []) {
            return collect();
        }
        $ids = LibraryTopic::query()->where('grade_level_id', $group->grade_level_id)->where(function ($query) use ($scopes) {
            foreach ($scopes as [$framework, $code]) {
                $query->orWhere(fn ($q) => $q->where('subject_code', $code)->whereHas('framework', fn ($f) => $f->where('code', $framework)));
            }
        })->limit(5001)->pluck('id');
        abort_if($ids->count() > 5000, 422, 'The library subject exceeds its bounded catalogue.');

        return MasteryTopic::query()->where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)
            ->whereIn('library_topic_id', $ids)->get()->keyBy('library_topic_id');
    }

    /**
     * Published library practice questions for the group's topics, ordered
     * like the school's own: due first, weakest topic first, then earlier
     * topics in the library's prerequisite graph.
     *
     * @return list<LibraryItemVersion>
     */
    public function candidates(int $learner, TeachingGroup $group, Collection $weakness, string $today): array
    {
        if (! app(LibrarySchools::class)->enabled()) {
            return [];
        }
        app(TopicGraph::class)->overlay((int) $group->subject_id);
        $topics = $this->topics($group);
        if ($topics->isEmpty()) {
            return [];
        }
        $items = LibraryItem::query()->whereIn('library_topic_id', $topics->keys())->where('kind', 'practice_question')->whereNotNull('published_version_id')
            ->orderBy('id')->limit(1001)->get();
        abort_if($items->count() > 1000, 422, 'The library practice bank exceeds its bounded catalogue.');
        $versions = LibraryItemVersion::query()->whereIn('id', $items->pluck('published_version_id'))->where('state', 'published')->get()->keyBy('id');
        $memories = PracticeMemory::query()->where('learner_id', $learner)->whereIn('library_item_id', $items->pluck('id'))->get()->keyBy('library_item_id');
        $depth = $this->depths($topics->keys()->all());
        $available = [];
        foreach ($items as $item) {
            $version = $versions->get($item->published_version_id);
            $topic = $topics->get($item->library_topic_id);
            if ($version === null || $topic === null || $version->estimated_seconds === null) {
                continue;
            }
            $memory = $memories->get($item->id);
            $available[] = ['version' => $version, 'due' => $memory === null || $memory->due_on->toDateString() <= $today ? 0 : 1,
                'weakness' => $weakness->get($topic->id) ?? -1, 'depth' => $depth[$item->library_topic_id] ?? 1, 'id' => (int) $item->id];
        }
        usort($available, fn ($a, $b) => [$a['due'], $a['weakness'], $a['depth'], $a['id']] <=> [$b['due'], $b['weakness'], $b['depth'], $b['id']]);

        return array_column($available, 'version');
    }

    /**
     * One set entry's library question, checked afresh on every read and
     * review: the school's switch, the group's frameworks and the published
     * version. A version stays usable after a newer one is published, as a
     * school's own approved versions do.
     *
     * @return array{0: LibraryItemVersion, 1: LibraryItem, 2: MasteryTopic}
     */
    public function entry(TeachingGroup $group, int $versionId): array
    {
        abort_unless(app(LibrarySchools::class)->enabled(), 404);
        $version = LibraryItemVersion::query()->where('state', 'published')->findOrFail($versionId);
        $item = LibraryItem::query()->where('kind', 'practice_question')->whereNotNull('published_version_id')->findOrFail($version->library_item_id);
        $topic = $this->topics($group)->get($item->library_topic_id);
        abort_if($topic === null, 404);

        return [$version, $item, $topic];
    }

    /** @return array<int, int> library topic id => depth */
    private function depths(array $topicIds): array
    {
        $edges = [];
        foreach (LibraryTopicPrerequisite::query()->whereIn('library_topic_id', $topicIds)->whereIn('prerequisite_id', $topicIds)->get() as $edge) {
            $edges[$edge->library_topic_id][$edge->prerequisite_id] = true;
        }
        foreach ($topicIds as $id) {
            $edges[(int) $id] ??= [];
        }

        return app(LibraryCatalogue::class)->depths($edges);
    }
}
