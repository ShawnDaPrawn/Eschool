<?php

declare(strict_types=1);

namespace App\Services\Learning\Digests;

use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;

class DigestAuthority
{
    public function enabled(bool $write = false): void
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->refresh()?->isActive(), 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('digest') : $gate->allows('digest'), 403);
    }

    public function actor(User $actor, bool $write = false): User
    {
        $this->enabled($write);
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

    public function manager(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $academic = app(AcademicAuthority::class);
        abort_unless((array_intersect($roles, ['Admin', 'Super Admin']) !== [] && $academic->manages($fresh)) || $academic->currentLeadership($fresh, 'principal', 0), 403);

        return $fresh;
    }

    public function currentLearner(Students $learner): bool
    {
        return User::acrossSchools()->whereKey($learner->user_id)->where('status', 1)->exists()
            && SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $learner->user_id)->where('role', 'Student')->exists()
            && Enrolment::onDate(app(AcademicAuthority::class)->today())->where('learner_id', $learner->id)->exists()
            && app(ApprovalService::class)->approved($learner);
    }

    public function family(User $actor, int $learnerId): Students
    {
        $fresh = $this->parent($actor);
        $learner = Students::findOrFail($learnerId);
        abort_unless($this->currentLearner($learner) && app(GuardianService::class)->guardianUserIds($learner)->contains((int) $fresh->id), 404);

        return $learner;
    }

    public function candidates(User $actor): Builder
    {
        $fresh = $this->parent($actor);
        $parents = Parents::where('user_id', $fresh->id)->pluck('id')->all();
        $schoolId = app(CurrentSchool::class)->id();

        return Students::where(function ($q) use ($fresh, $parents, $schoolId): void {
            $q->whereExists(fn ($links) => $links->selectRaw('1')->from('guardian_links')->where('guardian_links.school_id', $schoolId)
                ->whereColumn('guardian_links.learner_id', 'students.id')->where('guardian_links.guardian_user_id', $fresh->id)->where('guardian_links.status', 'active'));
            if ($parents !== []) {
                $q->orWhere(fn ($legacy) => $legacy->where(fn ($ids) => $ids->whereIn('father_id', $parents)->orWhereIn('mother_id', $parents)->orWhereIn('guardian_id', $parents))
                    ->whereNotExists(fn ($links) => $links->selectRaw('1')->from('guardian_links')->where('guardian_links.school_id', $schoolId)
                        ->whereColumn('guardian_links.learner_id', 'students.id')->where('guardian_links.guardian_user_id', $fresh->id)));
            }
        });
    }
}
