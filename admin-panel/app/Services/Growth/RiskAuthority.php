<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Growth\Intervention;
use App\Models\Growth\RiskCaseload;
use App\Models\Growth\RiskObservation;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\Growth\LearnerNotesPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RiskAuthority
{
    public function enabled(bool $write = false): void
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
        abort_unless(School::active()->whereKey($school->id())->exists(), 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('early_warning') : $gate->allows('early_warning'), 403);
    }

    public function actor(User $actor): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
        abort_unless(School::active()->whereKey($school->id())->exists(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(AcademicAuthority::class)->staff($actor);
        abort_if($fresh->password_change_required, 403, 'Complete your password change before opening restricted records.');

        return $fresh;
    }

    public function year(int $year, bool $write = false): void
    {
        $this->enabled($write);
        SessionYear::withTrashed()->findOrFail($year);
        abort_if($write && $year !== (int) schoolSetting('session_year'), 423, 'Historical interventions are read-only.');
    }

    /** Dated principal authority, never an automatic Super Admin grant. */
    public function principal(User $actor, bool $write = false, bool $requireModule = true): User
    {
        $fresh = $this->actor($actor);
        abort_unless(app(AcademicAuthority::class)->currentLeadership($fresh, 'principal', 0), 403);
        if ($requireModule) {
            $this->enabled($write);
        }

        return $fresh;
    }

    /** Aggregate permission does not confer caseload or intervention-body access. */
    public function aggregate(User $actor): User
    {
        $fresh = $this->actor($actor);
        $authority = app(AcademicAuthority::class);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $admin = array_intersect($roles, ['Admin', 'Super Admin']) !== [] && $authority->manages($fresh);
        abort_unless($admin || $authority->currentLeadership($fresh, 'principal', 0), 403);

        return $fresh;
    }

    public function currentGrade(int $learner, int $year): int
    {
        return $this->gradeOn($learner, $year, app(AcademicAuthority::class)->today());
    }

    /** The learner's grade from their enrolment in the year on $date. */
    public function gradeOn(int $learner, int $year, string $date): int
    {
        Students::findOrFail($learner);
        $enrolment = Enrolment::where('learner_id', $learner)->where('session_year_id', $year)->onDate($date)
            ->with('homeroom.class')->orderByDesc('starts_on')->firstOrFail();
        $grade = $enrolment->homeroom?->class?->grade_level_id;
        abort_unless($grade !== null, 422, 'Map the learner enrolment to a grade first.');

        return (int) $grade;
    }

    public function scope(User $actor, int $learner, int $year, int $grade, bool $write = false): User
    {
        $fresh = $this->actor($actor);
        $this->year($year, $write);
        Students::findOrFail($learner);
        if (app(AcademicAuthority::class)->currentLeadership($fresh, 'grade_head', $grade)) {
            if ($write) {
                abort_unless($this->currentGrade($learner, $year) === $grade, 404);
            }

            return $fresh;
        }
        app(SensitiveRecordsAccess::class)->actor($fresh);
        $today = app(AcademicAuthority::class)->today();
        abort_unless(RiskCaseload::where('learner_id', $learner)->where('session_year_id', $year)->where('professional_id', $fresh->id)
            ->whereNull('revoked_at')->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists(), 404);

        return $fresh;
    }

    /** Administers the explicit grant only; never loads intervention text. */
    public function administer(User $actor, int $learner, int $year): User
    {
        $fresh = $this->actor($actor);
        $this->year($year);
        Students::findOrFail($learner);
        $authority = app(AcademicAuthority::class);
        if ($authority->currentLeadership($fresh, 'principal', 0)) {
            return $fresh;
        }
        $grade = RiskObservation::where('learner_id', $learner)->where('session_year_id', $year)->latest('id')->value('grade_level_id')
            ?? Intervention::where('learner_id', $learner)->where('session_year_id', $year)->latest('id')->value('grade_level_id');
        // A year still to come has no enrolment today: its grade is the one on its first day
        // (caseload renewals before the switch, Action Plan 1.20). Other years read today.
        $start = SessionYear::withTrashed()->findOrFail($year)->start_date;
        $grade ??= $this->gradeOn($learner, $year, max(substr((string) $start, 0, 10), $authority->today()));
        abort_unless($authority->currentLeadership($fresh, 'grade_head', (int) $grade), 403);

        return $fresh;
    }

    public function caseKind(User $actor, int $learner, int $year, int $grade, bool $write = false): string
    {
        $fresh = $this->actor($actor);
        $this->year($year, $write);
        if (app(AcademicAuthority::class)->currentLeadership($fresh, 'grade_head', $grade)) {
            return 'leadership';
        }
        try {
            $this->scope($fresh, $learner, $year, $grade, $write);

            return 'professional';
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            app(LearnerNotesPolicy::class)->academic($fresh, $learner, null, $year);

            return 'academic';
        }
    }

    /** An explicit school-action owner must retain the original kind of authority. */
    public function caseActor(User $actor, int $learner, int $year, int $grade, bool $write = false, ?string $requiredKind = null): User
    {
        $fresh = $this->actor($actor);
        $this->year($year);
        $this->enabled($write);
        if ($requiredKind === null) {
            $this->caseKind($fresh, $learner, $year, $grade, $write);
        } elseif ($requiredKind === 'professional') {
            app(SensitiveRecordsAccess::class)->actor($fresh);
            Students::findOrFail($learner);
            $today = app(AcademicAuthority::class)->today();
            abort_unless(RiskCaseload::where('learner_id', $learner)->where('session_year_id', $year)->where('professional_id', $fresh->id)
                ->whereNull('revoked_at')->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists(), 404);
        } elseif ($requiredKind === 'leadership') {
            abort_unless(app(AcademicAuthority::class)->currentLeadership($fresh, 'grade_head', $grade), 404);
        } elseif ($requiredKind === 'academic') {
            app(LearnerNotesPolicy::class)->academic($fresh, $learner, null, $year);
        } else {
            abort(404);
        }

        return $fresh;
    }
}
