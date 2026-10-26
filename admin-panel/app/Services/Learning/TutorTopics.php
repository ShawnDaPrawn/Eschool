<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryTopic;
use App\Services\Academics\MarkDefinitions;
use App\Services\Growth\TopicGraph;
use Illuminate\Support\Collection;

/** Only published current-term curriculum labels in the authorized group. */
class TutorTopics
{
    public function forGroup(TeachingGroup $group): Collection
    {
        app(TopicGraph::class)->sync((int) $group->subject_id);
        $plans = app(MarkDefinitions::class)->plans($group);
        $nodes = app(TopicGraph::class)->nodes((int) $group->subject_id)
            ->filter(fn ($node) => (int) $node->grade_level_id === (int) $group->grade_level_id && $node->source !== null
                && $plans->contains(fn ($p) => (int) $p->curriculum_plan_id === (int) $node->source->curriculum_plan_id && (int) $p->term_id === (int) $node->source->term_id))->values();
        abort_if($nodes->count() > 200, 422, 'The published topic catalogue exceeds its bounded size.');

        return $nodes;
    }

    public function topic(TeachingGroup $group, int $id): MasteryTopic
    {
        $node = $this->forGroup($group)->firstWhere('id', $id);
        abort_if($node === null, 404);

        return $node;
    }
}
