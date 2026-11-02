<?php

declare(strict_types=1);

namespace App\Services\EarlyYears;

use App\Models\Academics\HomeroomAllocation;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Who may use the early years tools (Action Plan 4.3).
 *
 * Writing (diary, observations, concerns) needs a current, dated homeroom
 * allocation to the learner's class in the current school year. Reading a
 * class or a portfolio also allows the school's dated principal and the
 * grade head of the class's grade. The school's early_years module decides
 * whether any of it is available; grade labels are never guessed.
 */
class EarlyYearsAuthority
{
    public const MODULE = 'early_years';

    public function actor(User $actor, bool $write = false): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
        abort_unless(School::active()->whereKey($school->id())->exists(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(AcademicAuthority::class)->staff($actor);
        abort_if((bool) $fresh->password_change_required, 403, 'Complete your password change first.');
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable(self::MODULE) : $gate->allows(self::MODULE), 403);

        return $fresh;
    }

    public function year(): SessionYear
    {
        return SessionYear::findOrFail((int) schoolSetting('session_year'));
    }

    public function today(): string
    {
        return app(AcademicAuthority::class)->today();
    }

    /** @return list<int> class_section ids the person is allocated to today */
    public function allocated(User $fresh): array
    {
        $teachers = app(AcademicAuthority::class)->teachers($fresh);
        if ($teachers === []) {
            return [];
        }
        $today = $this->today();

        return HomeroomAllocation::whereIn('teacher_id', $teachers)->where('session_year_id', $this->year()->id)
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
            ->pluck('class_section_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
    }

    private function grade(ClassSection $section): ?int
    {
        $grade = $section->class?->grade_level_id;

        return $grade === null ? null : (int) $grade;
    }

    /** The dated principal, or the grade head of this class's grade. */
    public function leads(User $fresh, ClassSection $section): bool
    {
        $authority = app(AcademicAuthority::class);
        $year = (int) $this->year()->id;
        $grade = $this->grade($section);

        return $authority->leads($fresh, 'principal', 0, $year) || ($grade !== null && $authority->leads($fresh, 'grade_head', $grade, $year));
    }

    public function classRead(User $actor, int $sectionId): ClassSection
    {
        $fresh = $this->actor($actor);
        $section = ClassSection::with('class')->findOrFail($sectionId);
        abort_unless(in_array($sectionId, $this->allocated($fresh), true) || $this->leads($fresh, $section), 404);

        return $section;
    }

    public function classWrite(User $actor, int $sectionId): ClassSection
    {
        $fresh = $this->actor($actor, true);
        // Writing is a class teacher's job: other staff roles are refused outright.
        abort_if(app(AcademicAuthority::class)->teachers($fresh) === [], 403);
        $section = ClassSection::with('class')->findOrFail($sectionId);
        abort_unless(in_array($sectionId, $this->allocated($fresh), true), 404);

        return $section;
    }

    /** The learner's class today in the current school year. */
    public function learnerSection(int $learnerId): int
    {
        Students::findOrFail($learnerId);
        $section = Enrolment::where('learner_id', $learnerId)->where('session_year_id', $this->year()->id)->onDate($this->today())
            ->orderByDesc('starts_on')->value('class_section_id');
        abort_if($section === null, 404);

        return (int) $section;
    }

    public function learnerRead(User $actor, int $learnerId): ClassSection
    {
        return $this->classRead($actor, $this->learnerSection($learnerId));
    }

    public function learnerWrite(User $actor, int $learnerId): ClassSection
    {
        return $this->classWrite($actor, $this->learnerSection($learnerId));
    }

    /** @return Collection<int, Students> learners enrolled in the class today, with their user */
    public function roster(int $sectionId): Collection
    {
        $ids = Enrolment::where('class_section_id', $sectionId)->where('session_year_id', $this->year()->id)->onDate($this->today())->pluck('learner_id');

        return Students::with('user')->whereIn('id', $ids)->orderBy('id')->limit(200)->get()
            ->sortBy(fn (Students $learner) => mb_strtolower((string) $learner->user?->full_name))->values();
    }

    /** @return Collection<int, ClassSection> classes this person may open */
    public function classes(User $actor): Collection
    {
        $fresh = $this->actor($actor);
        $allocated = $this->allocated($fresh);
        $year = (int) $this->year()->id;
        $sections = Enrolment::where('session_year_id', $year)->onDate($this->today())->distinct()->pluck('class_section_id')
            ->merge($allocated)->map(fn ($id) => (int) $id)->unique()->all();

        return ClassSection::with('class', 'section')->whereIn('id', $sections)->orderBy('id')->limit(500)->get()
            ->filter(fn (ClassSection $section) => in_array((int) $section->id, $allocated, true) || $this->leads($fresh, $section))->values();
    }

    /** Development areas are school settings: academic structure managers or the dated principal. */
    public function manage(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        $authority = app(AcademicAuthority::class);
        abort_unless($authority->manages($fresh) || $authority->leads($fresh, 'principal', 0, (int) $this->year()->id), 403);

        return $fresh;
    }

    public function canManage(User $actor): bool
    {
        try {
            $this->manage($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function available(User $user): bool
    {
        try {
            return $this->canManage($user) || $this->classes($user)->isNotEmpty();
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
