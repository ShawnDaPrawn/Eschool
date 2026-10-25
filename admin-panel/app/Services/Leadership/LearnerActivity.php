<?php

declare(strict_types=1);

namespace App\Services\Leadership;

use App\Models\Leadership\LearnerActivityDay;
use App\Models\People\Enrolment;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Transitions\LearnerAcademicAccess;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class LearnerActivity
{
    public function record(User $actor): void
    {
        $context = app(CurrentSchool::class);
        if (! $context->isSet() || $context->isAcrossSchools() || $context->isImpersonating() || ! app(ModuleGate::class)->allows('leadership_dashboard')) {
            return;
        }
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        if (! in_array('Student', $policy->roles($fresh), true) || $fresh->password_change_required) {
            return;
        }
        app(LearnerAcademicAccess::class)->assertUser($fresh);
        $learner = Students::where('user_id', $fresh->id)->first();
        if ($learner === null || ! app(ApprovalService::class)->approved($learner)) {
            return;
        }
        $date = app(AcademicAuthority::class)->today();
        $enrolments = Enrolment::with('homeroom.class')->where('learner_id', $learner->id)->onDate($date)->get();
        // Ambiguous enrolments are not assigned to an arbitrary grade.
        if ($enrolments->count() !== 1) {
            return;
        }
        $e = $enrolments->sole();
        $grade = $e->homeroom?->class?->grade_level_id;
        if ($grade === null) {
            return;
        }
        LearnerActivityDay::firstOrCreate(['learner_id' => $learner->id, 'on_date' => $date], ['grade_level_id' => $grade, 'session_year_id' => $e->session_year_id]);
    }
}
