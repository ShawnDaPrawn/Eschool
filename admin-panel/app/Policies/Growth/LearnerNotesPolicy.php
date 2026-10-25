<?php

declare(strict_types=1);

namespace App\Policies\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\LearnerNoteAssignment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class LearnerNotesPolicy
{
    public function enabled(bool $write = false): void
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
        abort_unless(School::query()->active()->whereKey($school->id())->exists(), 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('academics') : $gate->allows('academics'), 403);
    }

    public function year(int $yearId, bool $write = false): void
    {
        $this->enabled($write);
        SessionYear::withTrashed()->findOrFail($yearId);
        abort_if($write && $yearId !== (int) schoolSetting('session_year'), 423, 'Historical learner notes are read-only.');
    }

    public function academic(User $actor, int $learnerId, ?int $subjectId, int $yearId): User
    {
        $this->year($yearId);
        $authority = app(AcademicAuthority::class);
        $fresh = $authority->staff($actor);
        Students::query()->findOrFail($learnerId);
        if ($subjectId !== null) {
            Subject::query()->findOrFail($subjectId);
        }
        // Authority is current, even for retained notes from previous years.
        $today = $authority->today();
        $groups = TeachingGroup::query()->where('active', true)->where('session_year_id', (int) schoolSetting('session_year'))
            ->when($subjectId !== null, fn ($q) => $q->where('subject_id', $subjectId))
            ->whereIn('id', TeachingGroupMembership::query()->where('learner_id', $learnerId)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('teaching_group_id'))->get();
        foreach ($groups as $group) {
            $allocated = TeachingAllocation::query()->where('teaching_group_id', $group->id)->whereIn('teacher_id', $authority->teachers($fresh))
                ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists();
            if ($allocated || $authority->leads($fresh, 'grade_head', (int) $group->grade_level_id, (int) $group->session_year_id)
                || $authority->leads($fresh, 'principal', (int) $group->grade_level_id, (int) $group->session_year_id)) {
                return $fresh;
            }
        }
        abort(404);
    }

    public function counselling(User $actor, int $learnerId): User
    {
        $this->enabled();
        $fresh = app(SensitiveRecordsAccess::class)->actor($actor);
        Students::query()->findOrFail($learnerId);
        $today = app(AcademicAuthority::class)->today();
        abort_unless(LearnerNoteAssignment::query()->where('learner_id', $learnerId)->where('staff_user_id', $fresh->id)->whereNull('revoked_at')
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists(), 404);

        return $fresh;
    }

    /** Assignment administration grants no authority to load a counselling body. */
    public function principal(User $actor): User
    {
        $this->enabled(true);
        abort_if(PlatformStaffIdentity::query()->where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(AcademicAuthority::class)->staff($actor);
        $today = app(AcademicAuthority::class)->today();
        abort_unless(AcademicLeadership::query()->where('user_id', $fresh->id)->where('kind', 'principal')->whereNull('grade_level_id')
            ->where('session_year_id', (int) schoolSetting('session_year'))->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists(), 403);

        return $fresh;
    }

    public function assign(User $actor, int $learnerId): User
    {
        $fresh = $this->principal($actor);
        Students::query()->findOrFail($learnerId);

        return $fresh;
    }

    public function scope(User $actor, int $learnerId, ?int $subjectId, int $yearId, string $visibility): User
    {
        $this->year($yearId);
        abort_unless(in_array($visibility, ['tutor', 'staff', 'counselling'], true), 404);
        if ($visibility === 'counselling') {
            if ($subjectId !== null) {
                Subject::query()->findOrFail($subjectId);
            }

            return $this->counselling($actor, $learnerId);
        }

        return $this->academic($actor, $learnerId, $subjectId, $yearId);
    }
}
