<?php

declare(strict_types=1);

namespace App\Services\Discipline;

use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Discipline\DisciplineRecord;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class DisciplineAuthority
{
    public function enabled(bool $write = false): void
    {
        $c = app(CurrentSchool::class);
        abort_unless($c->isSet() && ! $c->isAcrossSchools() && ! $c->isImpersonating(), 403);
        abort_unless(School::active()->whereKey($c->id())->exists(), 403);
        $g = app(ModuleGate::class);
        $g->forget();
        abort_unless($write ? $g->writable('discipline') : $g->allows('discipline'), 403);
    }

    public function actor(User $actor, bool $staff = true): User
    {
        $this->enabled();
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = $staff ? app(AcademicAuthority::class)->staff($actor) : app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403);

        return $fresh;
    }

    public function year(int $year, bool $write = false): SessionYear
    {
        $this->enabled($write);
        $row = SessionYear::withTrashed()->findOrFail($year);
        abort_if($write && ($year !== (int) schoolSetting('session_year') || $row->trashed()), 423, 'New discipline changes require the current school year.');

        return $row;
    }

    public function manages(User $actor): bool
    {
        $fresh = $this->actor($actor);
        $a = app(AcademicAuthority::class);
        $roles = app(ProfilePolicy::class)->roles($fresh);

        return $a->currentLeadership($fresh, 'principal', 0) || (array_intersect($roles, ['Admin', 'Super Admin']) !== [] && $a->manages($fresh));
    }

    public function manage(User $actor, int $year, bool $write = false): User
    {
        $fresh = $this->actor($actor);
        $this->year($year, $write);
        abort_unless($this->manages($fresh), 403);

        return $fresh;
    }

    public function head(User $actor, int $year, int $grade, bool $write = false): User
    {
        $fresh = $this->actor($actor);
        $this->year($year);
        $this->enabled($write);
        abort_unless($this->manages($fresh) || app(AcademicAuthority::class)->currentLeadership($fresh, 'grade_head', $grade), 403);

        return $fresh;
    }

    public function principal(User $actor, int $year, bool $write = false): User
    {
        $fresh = $this->actor($actor);
        $this->year($year);
        $this->enabled($write);
        abort_unless(app(AcademicAuthority::class)->currentLeadership($fresh, 'principal', 0), 403);

        return $fresh;
    }

    public function grade(int $learner, int $year, string $date): int
    {
        Students::findOrFail($learner);
        $enrolment = Enrolment::where('learner_id', $learner)->where('session_year_id', $year)->onDate($date)->with('homeroom.class')->orderByDesc('starts_on')->firstOrFail();
        $grade = $enrolment->homeroom?->class?->grade_level_id;
        abort_unless($grade !== null, 422, 'Map the dated enrolment to a grade first.');

        return (int) $grade;
    }

    private function teaches(User $actor, int $learner, int $year, string $date, ?int $grade = null): bool
    {
        $a = app(AcademicAuthority::class);
        $today = $a->today();
        $groups = TeachingGroup::where('session_year_id', $year)->where('active', true)->when($grade !== null, fn ($q) => $q->where('grade_level_id', $grade))->whereIn('id',
            TeachingGroupMembership::where('learner_id', $learner)->whereDate('starts_on', '<=', min($date, $today))->whereDate('ends_on', '>=', max($date, $today))->select('teaching_group_id'));

        return TeachingAllocation::whereIn('teaching_group_id', $groups->select('id'))->whereIn('teacher_id', $a->teachers($actor))
            ->whereDate('starts_on', '<=', min($date, $today))->whereDate('ends_on', '>=', max($date, $today))->exists();
    }

    public function record(User $actor, int $learner, int $year, string $date): int
    {
        $fresh = $this->actor($actor);
        $y = $this->year($year, true);
        $today = app(AcademicAuthority::class)->today();
        abort_unless($date >= $y->start_date && $date <= min($today, $y->end_date), 422, 'The occurrence must be within this school year and cannot be in the future.');
        $grade = $this->grade($learner, $year, $date);
        abort_unless($this->manages($fresh) || app(AcademicAuthority::class)->currentLeadership($fresh, 'grade_head', $grade) || $this->teaches($fresh, $learner, $year, $date, $grade), 404);

        return $grade;
    }

    public function read(User $actor, DisciplineRecord $record): User
    {
        $fresh = $this->actor($actor);
        $this->year((int) $record->session_year_id);
        $head = app(AcademicAuthority::class)->currentLeadership($fresh, 'grade_head', (int) $record->grade_level_id);
        $own = (int) $record->recorded_by === (int) $fresh->id && $this->teaches($fresh, (int) $record->learner_id, (int) schoolSetting('session_year'), app(AcademicAuthority::class)->today());
        abort_unless($this->manages($fresh) || $head || $own, 404);

        return $fresh;
    }

    public function family(User $actor, int $learner, bool $acknowledge = false): User
    {
        $fresh = $this->actor($actor, false);
        $student = app(ReleasedMarks::class)->learner($fresh, $learner);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        if (in_array('Student', $roles, true) && (int) $student->user_id === (int) $fresh->id) {
            abort_unless(app(ApprovalService::class)->approved($student), 403);
            abort_if($acknowledge, 403);
        }
        if ($acknowledge) {
            abort_unless(in_array('Parent', $roles, true) && $fresh->email_verified_at !== null, 403);
        }

        return $fresh;
    }
}
