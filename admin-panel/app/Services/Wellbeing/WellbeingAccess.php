<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Models\Wellbeing\CareCase;
use App\Models\Wellbeing\CaseAssignment;
use App\Models\Wellbeing\Category;
use App\Models\Wellbeing\IncidentReport;
use App\Models\Wellbeing\TeamAssignment;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class WellbeingAccess
{
    public function environment(bool $write = false): void
    {
        $context = app(CurrentSchool::class);
        $context->school()?->refresh();
        abort_unless($context->isSet() && ! $context->isAcrossSchools() && ! $context->isImpersonating() && $context->school()?->isActive(), 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('wellbeing') : $gate->allows('wellbeing'), 404);
    }

    public function actor(User $actor, bool $write = false): User
    {
        $this->environment($write);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403, 'Complete your password change before opening private wellbeing records.');
        app(ApprovalService::class)->assertAccess($fresh);

        return $fresh;
    }

    public function configure(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        abort_unless(app(ProfilePolicy::class)->permission($fresh, 'wellbeing-configure') || $this->isPrincipal($fresh), 403);

        return $fresh;
    }

    public function isPrincipal(User $actor): bool
    {
        return app(AcademicAuthority::class)->leads($actor, 'principal', 0, (int) schoolSetting('session_year'));
    }

    public function professional(User $actor, bool $write = false): User
    {
        // Shared exclusions and fresh professional role precede restricted queries.
        $fresh = app(SensitiveRecordsAccess::class)->actor($actor);

        return $this->actor($fresh, $write);
    }

    public function professionalDuring(User $actor, string $from, string $to): User
    {
        $fresh = $this->professional($actor, true);
        abort_unless(SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->where('user_id', $fresh->id)->where('status', 'active')->whereIn('role', ['Counsellor', 'Safeguarding'])
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $from))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $to))->exists(), 422, 'The professional school role must cover the appointment.');

        return $fresh;
    }

    public function teamIds(User $actor): array
    {
        $fresh = $this->professional($actor);
        $today = app(AcademicAuthority::class)->today();

        return TeamAssignment::where('user_id', $fresh->id)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('category_id')->map(fn ($id) => (int) $id)->all();
    }

    public function category(User $actor, int $id, bool $write = false): Category
    {
        $this->professional($actor, $write);
        abort_unless(in_array($id, $this->teamIds($actor), true), 404);

        return Category::findOrFail($id);
    }

    public function report(User $actor, int $id, bool $write = false): IncidentReport
    {
        $this->professional($actor, $write);
        $metadata = IncidentReport::select(['id', 'category_id'])->whereIn('category_id', $this->teamIds($actor))->findOrFail($id);
        $this->category($actor, (int) $metadata->category_id, $write);

        return IncidentReport::findOrFail($id);
    }

    public function caseIds(User $actor): array
    {
        $fresh = $this->professional($actor);
        $today = app(AcademicAuthority::class)->today();

        return CaseAssignment::where('user_id', $fresh->id)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('support_case_id')->map(fn ($id) => (int) $id)->all();
    }

    public function case(User $actor, int $id, bool $write = false): CareCase
    {
        $this->professional($actor, $write);
        abort_unless(in_array($id, $this->caseIds($actor), true), 404);

        return CareCase::findOrFail($id);
    }

    public function learner(int $id): Students
    {
        $learner = Students::findOrFail($id);
        abort_unless(Enrolment::where('learner_id', $id)->where('session_year_id', (int) schoolSetting('session_year'))->onDate(app(AcademicAuthority::class)->today())->exists(), 422, 'Choose a currently enrolled learner.');

        return $learner;
    }
}
