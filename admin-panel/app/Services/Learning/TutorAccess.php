<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Learning\TutorSession;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class TutorAccess
{
    public function actor(User $actor, bool $write = false): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        app(ApprovalService::class)->assertAccess($fresh);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('tutor') : $gate->allows('tutor'), 403);

        return $fresh;
    }

    public function learner(User $actor, bool $write = false): Students
    {
        $fresh = $this->actor($actor, $write);
        abort_unless(in_array('Student', app(ProfilePolicy::class)->roles($fresh), true)
            && app(CurrentLearnerMembership::class)->users()->whereKey($fresh->id)->exists(), 404);
        $person = Students::where('user_id', $fresh->id)->firstOrFail();
        app(ReleasedMarks::class)->learner($fresh, (int) $person->id);
        abort_unless(app(ApprovalService::class)->approved($person), 403, 'Guardian approval required.');

        return $person;
    }

    public function group(User $actor, int $group, bool $write = false): TeachingGroup
    {
        $person = $this->learner($actor, $write);
        abort_unless(in_array($group, app(ContentPolicy::class)->learnerGroups($actor, (int) $person->id), true), 404);
        $row = TeachingGroup::with('year')->findOrFail($group);
        $today = app(AcademicAuthority::class)->today();
        abort_unless($row->active && $row->subject()->exists() && (int) $row->session_year_id === (int) schoolSetting('session_year')
            && substr((string) $row->year?->start_date, 0, 10) <= $today && substr((string) $row->year?->end_date, 0, 10) >= $today, 404);

        return $row;
    }

    public function session(User $actor, string $id, bool $write = false): TutorSession
    {
        $person = $this->learner($actor, $write);
        // Load only ownership/scope columns until the current enrollment is authorized.
        $meta = TutorSession::select('id', 'school_id', 'user_id', 'learner_id', 'teaching_group_id', 'subject_id', 'session_year_id', 'state', 'topic_id')->findOrFail($id);
        abort_unless((int) $meta->user_id === (int) $actor->id && (int) $meta->learner_id === (int) $person->id, 404);
        $group = $this->group($actor, (int) $meta->teaching_group_id, $write);
        abort_unless((int) $group->subject_id === (int) $meta->subject_id && (int) $group->session_year_id === (int) $meta->session_year_id, 404);
        abort_if($write && $meta->state !== 'open', 409, 'This tutor session is closed.');
        if ($meta->topic_id !== null) {
            app(TutorTopics::class)->topic($group, (int) $meta->topic_id);
        }

        return TutorSession::findOrFail($id);
    }
}
