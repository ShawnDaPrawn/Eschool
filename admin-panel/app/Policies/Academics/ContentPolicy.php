<?php

declare(strict_types=1);

namespace App\Policies\Academics;

use App\Models\Academics\ContentItem;
use App\Models\Academics\ContentVersion;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\People\Enrolment;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;
use App\Services\Academics\Transitions\LearnerAcademicAccess;
use App\Services\Platform\Tenancy\CurrentSchool;

class ContentPolicy
{
    private function activeSchool(): void
    {
        abort_unless(School::whereKey(app(CurrentSchool::class)->id())->where('status', 'active')->exists(), 404);
    }

    public function leads(User $actor, int $grade): bool
    {
        $a = app(AcademicAuthority::class);

        return $a->manages($actor) || $a->currentLeadership($actor, 'grade_head', $grade) || $a->currentLeadership($actor, 'principal', $grade);
    }

    public function group(User $actor, int $id): TeachingGroup
    {
        $this->activeSchool();
        app(AcademicAuthority::class)->staff($actor);
        $group = TeachingGroup::findOrFail($id);
        abort_unless($group->active, 404);
        if (! $this->leads($actor, (int) $group->grade_level_id)) {
            app(AcademicAuthority::class)->group($actor, $id, true);
        }

        return $group;
    }

    public function staff(User $actor, int $id, bool $write = false): ContentItem
    {
        $this->activeSchool();
        app(AcademicAuthority::class)->staff($actor);
        $item = ContentItem::with('draft', 'published')->findOrFail($id);
        if ($this->leads($actor, (int) $item->grade_level_id)) {
            return $item;
        }
        abort_unless((int) $item->author_id === (int) $actor->id, 404);
        $version = $item->draft ?? $item->published;
        abort_if($version === null, 404);
        foreach ($version->metadata['group_ids'] as $group) {
            $this->group($actor, (int) $group);
        }
        if ($write && $item->published !== null) {
            foreach ($item->published->metadata['group_ids'] as $group) {
                $this->group($actor, (int) $group);
            }
        }

        return $item;
    }

    public function version(User $actor, int $id, bool $write = false): ContentVersion
    {
        $version = ContentVersion::with('item', 'source')->findOrFail($id);
        $item = $this->staff($actor, (int) $version->content_item_id, $write);
        if (! $this->leads($actor, (int) $item->grade_level_id)) {
            foreach ($version->metadata['group_ids'] as $group) {
                $this->group($actor, (int) $group);
            }
        }

        return $version;
    }

    /** Fresh membership AND compatible physical enrolment; no stale v1 projection. */
    public function learnerGroups(User $actor, int $learner): array
    {
        $this->activeSchool();
        $person = app(ReleasedMarks::class)->learner($actor, $learner);
        if (app(LearnerAcademicAccess::class)->departed($person)) {
            return [];
        }
        $today = app(AcademicAuthority::class)->today();

        return TeachingGroupMembership::with('group')->where('learner_id', $learner)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->get()
            ->filter(function ($m) use ($learner, $today) {
                $g = $m->group;

                return $g !== null && $g->active && Enrolment::where('learner_id', $learner)->where('session_year_id', $g->session_year_id)->onDate($today)
                    ->whereHas('homeroom.class', fn ($q) => $q->where('grade_level_id', $g->grade_level_id))
                    ->when($g->class_section_id !== null, fn ($q) => $q->where('class_section_id', $g->class_section_id))->exists();
            })->pluck('teaching_group_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function reader(User $actor, int $learner, int $version): ContentVersion
    {
        $groups = $this->learnerGroups($actor, $learner);

        return ContentVersion::with('item')->where('state', 'published')->whereHas('item', fn ($q) => $q->where('active', true)
            ->whereColumn('content_items.published_version_id', 'content_versions.id')->whereHas('audienceGroups', fn ($g) => $g->whereIn('teaching_group_id', $groups)))->findOrFail($version);
    }
}
