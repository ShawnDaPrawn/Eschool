<?php

declare(strict_types=1);

namespace App\Services\PeerTutoring;

use App\Models\Academics\TeachingGroup;
use App\Models\PeerTutoring\PeerTutoringSession;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\FamilyForms\FamilyFormAccess;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class PeerAccess extends FamilyFormAccess
{
    public function actor(User $actor, bool $write = false, bool $revocation = false): User
    {
        $school = app(CurrentSchool::class);
        $school->school()?->refresh();
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists() || $fresh->password_change_required, 403);
        app(ApprovalService::class)->assertAccess($fresh);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('peer_tutoring') : $gate->allows('peer_tutoring'), 404);
        abort_unless($gate->allows('mastery'), 404);

        return $fresh;
    }

    public function familyLearner(User $actor, int $id, string $persona): Students
    {
        $fresh = $this->actor($actor);
        $learner = $this->learner($id);
        if ($persona === 'student' && in_array('Student', app(ProfilePolicy::class)->roles($fresh), true) && (int) $learner->user_id === (int) $fresh->id) {
            return $learner;
        }
        abort_unless($persona === 'parent', 403);
        $this->guardian($fresh, $id);

        return $learner;
    }

    public function group(User $actor, int $id, bool $write = false, ?string $on = null): TeachingGroup
    {
        $fresh = $this->actor($actor, $write);
        $academic = app(AcademicAuthority::class);
        $group = $academic->group($fresh, $id);
        abort_unless($group->active && (int) $group->session_year_id === (int) schoolSetting('session_year'), 404);
        if (! $academic->manages($fresh) && ! $academic->leads($fresh, 'grade_head', (int) $group->grade_level_id, (int) $group->session_year_id, $on)
            && ! $academic->leads($fresh, 'principal', (int) $group->grade_level_id, (int) $group->session_year_id, $on)) {
            $academic->group($fresh, $id, true, $on);
        }

        return $group;
    }

    public function staffSession(User $actor, PeerTutoringSession $session, bool $write = false): TeachingGroup
    {
        $group = $this->group($actor, (int) $session->teaching_group_id, $write);
        $a = app(AcademicAuthority::class);
        abort_unless((int) $session->supervisor_user_id === (int) $actor->id || $a->manages($actor)
            || $a->leads($actor, 'grade_head', (int) $group->grade_level_id, (int) $group->session_year_id)
            || $a->leads($actor, 'principal', (int) $group->grade_level_id, (int) $group->session_year_id), 404);

        return $group;
    }
}
