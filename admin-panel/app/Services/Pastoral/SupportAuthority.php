<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Pastoral\SupportAssignment;
use App\Models\Pastoral\SupportDisclosure;
use App\Models\Pastoral\SupportPlan;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class SupportAuthority
{
    public function actor(User $actor, bool $write = false): User
    {
        $context = app(CurrentSchool::class);
        abort_unless($context->isSet() && ! $context->isAcrossSchools() && ! $context->isImpersonating(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(AcademicAuthority::class)->staff($actor);
        $context->school()?->refresh();
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('support_plans') : $gate->allows('support_plans'), 403);

        return $fresh;
    }

    public function principal(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        abort_unless(app(AcademicAuthority::class)->leads($fresh, 'principal', 0, (int) schoolSetting('session_year')), 403);

        return $fresh;
    }

    public function professional(User $actor, bool $write = false): User
    {
        $fresh = app(SensitiveRecordsAccess::class)->actor($actor);

        return $this->actor($fresh, $write);
    }

    public function learner(int $id, bool $active = false): Students
    {
        $learner = Students::findOrFail($id);
        if ($active) {
            abort_unless(Enrolment::where('learner_id', $id)->onDate(app(AcademicAuthority::class)->today())->exists(), 422, 'Choose a currently enrolled learner.');
        }

        return $learner;
    }

    public function caseload(User $actor, int $learner, bool $write = false): User
    {
        $fresh = $this->professional($actor, $write);
        $today = app(AcademicAuthority::class)->today();
        abort_unless(SupportAssignment::where('user_id', $fresh->id)->where('learner_id', $learner)->where('active', true)
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists(), 403);

        return $fresh;
    }

    public function plan(User $actor, int $id, bool $write = false): SupportPlan
    {
        $fresh = $this->professional($actor, $write);
        $today = app(AcademicAuthority::class)->today();
        $assigned = SupportAssignment::where('user_id', $fresh->id)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('learner_id');
        $metadata = SupportPlan::select('id', 'learner_id')->whereIn('learner_id', $assigned)->findOrFail($id);
        $this->caseload($fresh, (int) $metadata->learner_id, $write);

        return SupportPlan::findOrFail($id);
    }

    public function teacher(User $actor): User
    {
        $fresh = $this->actor($actor);
        abort_unless(in_array('Teacher', app(ProfilePolicy::class)->roles($fresh), true), 403);

        return $fresh;
    }

    /** No encrypted support-plan body is loaded along the teacher path. */
    public function disclosure(User $actor, int $id): SupportDisclosure
    {
        $fresh = $this->teacher($actor);
        $today = app(AcademicAuthority::class)->today();
        $row = SupportDisclosure::where('user_id', $fresh->id)->where('active', true)->whereDate('expires_on', '>=', $today)->findOrFail($id);
        abort_unless(Students::whereKey($row->learner_id)->exists() && Enrolment::where('learner_id', $row->learner_id)->onDate($today)->exists(), 404);
        abort_unless(SupportPlan::whereKey($row->support_plan_id)->where('learner_id', $row->learner_id)->where('state', 'active')->where('accommodation_revision', $row->source_accommodation_revision)->exists(), 404);

        return $row;
    }
}
