<?php

declare(strict_types=1);

namespace App\Services\Learning\Tests;

use App\Models\Academics\ContentVersion;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeItemVersion;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Services\Academics\MarkDefinitions;
use App\Services\Growth\TopicGraph;
use App\Services\Learning\Library\LibraryPractice;
use App\Services\Learning\Tests\Marking\TestMarkers;
use Illuminate\Support\Collection;

/**
 * The questions a test may be assembled from, per topic: the school's own
 * approved questions used in the group (question banks, Action Plan 3.6)
 * and, where the school's library switch is on, published library practice
 * questions for the group's framework topics (Action Plan 3.3). Only kinds
 * a marker handles today are offered (TestMarkers::ASSEMBLED).
 */
class TestQuestionPool
{
    /**
     * The group's topics a test may cover: its published curriculum topics,
     * then the library topics mirrored for its frameworks.
     *
     * @return array<int, array{id: int, name: string, source: string}>
     */
    public function topics(TeachingGroup $group): array
    {
        $plans = app(MarkDefinitions::class)->plans($group);
        $topics = [];
        foreach (app(TopicGraph::class)->nodes((int) $group->subject_id) as $node) {
            $source = $node->source;
            if ((int) $node->grade_level_id === (int) $group->grade_level_id && $source !== null
                && $plans->contains(fn ($p) => (int) $p->curriculum_plan_id === (int) $source->curriculum_plan_id && (int) $p->term_id === (int) $source->term_id)) {
                $topics[(int) $node->id] = ['id' => (int) $node->id, 'name' => (string) $node->name, 'source' => 'school'];
            }
        }
        foreach (app(LibraryPractice::class)->topics($group) as $mirror) {
            $topics[(int) $mirror->id] = ['id' => (int) $mirror->id, 'name' => (string) $mirror->name, 'source' => 'library'];
        }
        abort_if(count($topics) > 400, 422, 'The topic catalogue exceeds its bounded size.');

        return $topics;
    }

    /**
     * Candidates per topic. $readable limits school questions to these
     * published content versions (what the learner may read); null means the
     * versions published to the group (what its teacher sees).
     *
     * @param  list<int>  $topicIds
     * @param  list<int>|null  $readable
     * @return array<int, list<array{key: string, source: string, version: PracticeItemVersion|LibraryItemVersion, item_id: int, seconds: int}>>
     */
    public function candidates(TeachingGroup $group, array $topicIds, ?array $readable = null): array
    {
        $pool = array_fill_keys($topicIds, []);
        $readable ??= $this->publishedToGroup($group);
        $items = PracticeItem::usedIn((int) $group->id)->where('active', true)->whereIn('topic_id', $topicIds)->orderBy('id')->limit(2001)->get();
        abort_if($items->count() > 2000, 422, 'The question bank exceeds its bounded catalogue.');
        $versions = PracticeItemVersion::whereIn('id', PracticeItemVersion::selectRaw('MAX(id)')->whereIn('practice_item_id', $items->pluck('id'))->groupBy('practice_item_id'))->get();
        $byId = $items->keyBy('id');
        $markers = app(TestMarkers::class);
        foreach ($versions as $version) {
            $item = $byId[$version->practice_item_id];
            if (! in_array((int) $version->content_version_id, $readable, true) || ! $markers->assembles($version->definition['kind'] ?? null)) {
                continue;
            }
            $pool[(int) $item->topic_id][] = ['key' => 'practice_item:'.$item->id, 'source' => 'school', 'version' => $version, 'item_id' => (int) $item->id,
                'seconds' => (int) $version->estimated_seconds];
        }
        $mirrors = $this->mirrors($group, $topicIds);
        if ($mirrors->isNotEmpty()) {
            $library = LibraryItem::query()->whereIn('library_topic_id', $mirrors->keys())->where('kind', 'practice_question')->whereNotNull('published_version_id')
                ->orderBy('id')->limit(2001)->get();
            abort_if($library->count() > 2000, 422, 'The library practice bank exceeds its bounded catalogue.');
            $published = LibraryItemVersion::query()->whereIn('id', $library->pluck('published_version_id'))->where('state', 'published')->get()->keyBy('id');
            foreach ($library as $item) {
                $version = $published->get($item->published_version_id);
                $definition = $version?->practiceDefinition();
                if ($version === null || $version->estimated_seconds === null || ! $markers->assembles($definition['kind'] ?? null)) {
                    continue;
                }
                $pool[(int) $mirrors[$item->library_topic_id]->id][] = ['key' => 'library_item:'.$item->id, 'source' => 'library', 'version' => $version,
                    'item_id' => (int) $item->id, 'seconds' => (int) $version->estimated_seconds];
            }
        }

        return $pool;
    }

    /** @return list<int> published content versions whose audience includes the group */
    private function publishedToGroup(TeachingGroup $group): array
    {
        return ContentVersion::where('state', 'published')->whereHas('item', fn ($q) => $q->where('active', true)->where('subject_id', $group->subject_id)
            ->whereColumn('content_items.published_version_id', 'content_versions.id')->whereHas('audienceGroups', fn ($g) => $g->where('teaching_group_id', $group->id)))
            ->limit(2001)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** @return Collection<int, MasteryTopic> chosen mirror nodes keyed by library topic id */
    private function mirrors(TeachingGroup $group, array $topicIds): Collection
    {
        return app(LibraryPractice::class)->topics($group)->filter(fn ($node) => in_array((int) $node->id, $topicIds, true));
    }
}
