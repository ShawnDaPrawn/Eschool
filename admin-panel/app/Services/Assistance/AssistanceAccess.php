<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Models\Assistance\SupportThread;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;
use App\Services\Communication\CommunicationRules;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class AssistanceAccess
{
    public function actor(User $actor, bool $write = false): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating()
            && $school->school()?->refresh()?->isActive(), 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('ask_school') : $gate->allows('ask_school'), 403);
        $fresh = app(ProfilePolicy::class)->actor(User::findOrFail($actor->id));
        abort_if($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);

        return $fresh;
    }

    public function parent(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        abort_unless(in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true), 403);

        return $fresh;
    }

    public function family(User $actor, int $learner, bool $write = false): Students
    {
        $fresh = $this->parent($actor, $write);
        $person = app(ReleasedMarks::class)->learner($fresh, $learner);
        abort_unless(app(ApprovalService::class)->approved($person)
            && User::acrossSchools()->whereKey($person->user_id)->where('status', 1)->exists()
            && SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $person->user_id)->where('role', 'Student')->exists()
            && Enrolment::where('learner_id', $learner)->onDate(app(AcademicAuthority::class)->today())->exists(), 404);

        return $person;
    }

    public function office(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        $academic = app(AcademicAuthority::class);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        abort_unless(array_intersect($roles, ['Admin', 'Super Admin', 'Teacher', 'Facilitator', 'Counsellor', 'Safeguarding']) !== [], 403);
        abort_unless((array_intersect($roles, ['Admin', 'Super Admin']) !== [] && $academic->manages($fresh)
            && app(CommunicationRules::class)->manages($fresh)) || app(CommunicationRules::class)->principal($fresh), 403);

        return $fresh;
    }

    public function thread(User $actor, string $id, bool $write = false, bool $office = false): SupportThread
    {
        if ($office) {
            $fresh = $this->office($actor, $write);
        } else {
            $fresh = $this->parent($actor, $write);
        }
        $thread = SupportThread::findOrFail($id);
        abort_unless($thread->purged_at === null && $thread->expires_at->isFuture(), 404);
        if ($office) {
            abort_unless((int) $thread->assigned_user_id === (int) $fresh->id, 404);
            // A departed/revoked family cannot keep supplying its private school
            // record context to an old support assignment.
            $this->family(User::findOrFail($thread->parent_user_id), (int) $thread->learner_id);
        } else {
            abort_unless((int) $thread->parent_user_id === (int) $fresh->id, 404);
            $this->family($fresh, (int) $thread->learner_id, $write);
        }
        abort_if($write && $thread->state !== 'open', 409, 'This school-support thread is closed.');

        return $thread;
    }
}
