<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Pastoral\CoverAbsence;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CoverAuthority
{
    public function actor(User $actor, bool $write = false): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isImpersonating() && ! $school->isAcrossSchools(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(AcademicAuthority::class)->staff($actor);
        $school->school()?->refresh();
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('relief_cover') : $gate->allows('relief_cover'), 403);

        return $fresh;
    }

    public function available(User $actor, int $year): bool
    {
        try {
            $fresh = $this->actor($actor);

            return $this->manages($fresh, $year) || Teacher::where('user_id', $fresh->id)->get()->contains(fn ($teacher) => $this->own($fresh, (int) $teacher->id));
        } catch (HttpExceptionInterface) {
            return false;
        }
    }

    public function manages(User $actor, int $year): bool
    {
        $a = app(AcademicAuthority::class);

        return $a->manages($actor) || $a->leads($actor, 'principal', 0, $year);
    }

    public function teacher(int $id, string $date): Teacher
    {
        $teacher = Teacher::with('user:id,status,first_name,last_name')->findOrFail($id);
        abort_if(PlatformStaffIdentity::where('user_id', $teacher->user_id)->where('active', true)->exists(), 403);
        abort_unless((int) $teacher->user?->status === 1 && SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())
            ->where('user_id', $teacher->user_id)->where('role', 'Teacher')
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $date))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date))->exists(), 403);

        return $teacher;
    }

    public function own(User $actor, int $teacher): bool
    {
        return Teacher::whereKey($teacher)->where('user_id', $actor->id)->exists()
            && in_array('Teacher', app(ProfilePolicy::class)->roles($actor), true);
    }

    public function absence(User $actor, int $id, bool $write = false): CoverAbsence
    {
        $fresh = $this->actor($actor, $write);
        $absence = CoverAbsence::findOrFail($id);
        abort_unless($this->manages($fresh, (int) $absence->session_year_id) || $this->own($fresh, (int) $absence->teacher_id), 403);

        return $absence;
    }
}
