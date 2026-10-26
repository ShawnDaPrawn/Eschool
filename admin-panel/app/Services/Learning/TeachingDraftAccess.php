<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\ContentVersion;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\TeachingDraft;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\MarkDefinitions;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class TeachingDraftAccess
{
    public function actor(User $actor, string $module = 'teaching_assistant', bool $write = false): User
    {
        abort_unless(in_array($module, ['teaching_assistant', 'test_insights'], true), 403);
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        abort_unless(in_array('Teacher', $policy->roles($fresh), true), 403);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        app(ApprovalService::class)->assertAccess($fresh);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable($module) : $gate->allows($module), 403);

        return $fresh;
    }

    public function group(User $actor, int $id, bool $write = false, string $module = 'teaching_assistant'): TeachingGroup
    {
        $fresh = $this->actor($actor, $module, $write);
        // Even administrators and grade heads need their own current teaching allocation.
        $row = app(AcademicAuthority::class)->group($fresh, $id, true);
        $today = app(AcademicAuthority::class)->today();
        abort_unless($row->active && $row->subject()->exists() && (int) $row->session_year_id === (int) schoolSetting('session_year')
            && substr((string) $row->year?->start_date, 0, 10) <= $today && substr((string) $row->year?->end_date, 0, 10) >= $today, 404);

        return $row;
    }

    public function source(User $actor, TeachingGroup $group, int $id): ContentVersion
    {
        $group = $this->group($actor, (int) $group->id);
        $lead = app(ContentPolicy::class)->leads($actor, (int) $group->grade_level_id);
        ContentVersion::where('state', 'published')->whereHas('item', fn ($q) => $q->where('active', true)
            ->where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)->where('session_year_id', $group->session_year_id)
            ->whereColumn('published_version_id', 'content_versions.id')->when(! $lead, fn ($own) => $own->where('author_id', $actor->id))
            ->whereHas('audienceGroups', fn ($g) => $g->where('teaching_group_id', $group->id)))
            ->whereHas('source', fn ($q) => $q->where('scan_state', 'clean'))->select('id')->findOrFail($id);

        return app(ContentPolicy::class)->version($actor, $id);
    }

    /** Current active membership, physical placement and subject allocation, not a v1 roster. */
    public function learners(TeachingGroup $group): array
    {
        $today = app(AcademicAuthority::class)->today();
        $placements = Enrolment::where('session_year_id', $group->session_year_id)->onDate($today)
            ->whereHas('homeroom.class', fn ($q) => $q->where('grade_level_id', $group->grade_level_id))
            ->when($group->class_section_id !== null, fn ($q) => $q->where('class_section_id', $group->class_section_id))->select('learner_id');
        $members = SchoolMembership::where('role', 'Student')->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today))->select('user_id');
        $subject = TeachingGroupMembership::where('teaching_group_id', $group->id)
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('learner_id');
        $ids = Students::whereIn('id', $placements)->whereIn('id', $subject)->whereHas('user', fn ($q) => $q->where('status', 1)->whereIn('id', $members))
            ->orderBy('id')->limit(5001)->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_if(count($ids) > 5000, 422, 'This group exceeds the bounded analysis size.');

        return $ids;
    }

    public function learner(User $actor, TeachingGroup $group, int $id): Students
    {
        $this->group($actor, (int) $group->id);
        abort_unless(in_array($id, $this->learners($group), true), 404);
        $row = Students::findOrFail($id);
        abort_unless(app(ApprovalService::class)->approved($row), 403, 'Guardian approval required.');

        return $row;
    }

    public function topic(TeachingGroup $group, int $id): MasteryTopic
    {
        app(TopicGraph::class)->sync((int) $group->subject_id);
        $node = app(TopicGraph::class)->nodes((int) $group->subject_id)->firstWhere('id', $id);
        abort_unless($node !== null && (int) $node->grade_level_id === (int) $group->grade_level_id, 404);
        $source = $node->source;
        $plans = app(MarkDefinitions::class)->plans($group);
        abort_unless($source !== null && $plans->contains(fn ($p) => (int) $p->curriculum_plan_id === (int) $source->curriculum_plan_id && (int) $p->term_id === (int) $source->term_id), 404);

        return $node;
    }

    public function draft(User $actor, string $id, bool $write = false): TeachingDraft
    {
        $this->actor($actor, write: $write);
        $meta = TeachingDraft::select('id', 'author_id', 'teaching_group_id', 'content_version_id', 'learner_id', 'purged_at', 'created_at')->findOrFail($id);
        abort_unless((int) $meta->author_id === (int) $actor->id && $meta->purged_at === null && $meta->created_at->isAfter(now('UTC')->subDays(90)), 404);
        $group = $this->group($actor, (int) $meta->teaching_group_id, $write);
        $this->source($actor, $group, (int) $meta->content_version_id);
        if ($meta->learner_id !== null) {
            $this->learner($actor, $group, (int) $meta->learner_id);
        }

        return TeachingDraft::findOrFail($id);
    }
}
