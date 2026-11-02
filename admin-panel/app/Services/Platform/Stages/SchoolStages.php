<?php

declare(strict_types=1);

namespace App\Services\Platform\Stages;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\Platform\SchoolStage;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\RequestContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The age stage of each grade at a school (Action Plan 4.1): early years,
 * junior or high school. A grade the school has no row for uses the owner's
 * default. A learner's stage comes from the grade of their homeroom this
 * school year; a homeroom without a grade gives no stage, and nothing is
 * limited by stage for that learner.
 *
 * Specification: engineering/specs/platform/school-stages.md.
 */
class SchoolStages
{
    public const EARLY_YEARS = 'early_years';

    public const JUNIOR = 'junior';

    public const HIGH_SCHOOL = 'high_school';

    /** Stage keys and their names, youngest first. */
    public const STAGES = [self::EARLY_YEARS => 'Early years', self::JUNIOR => 'Junior', self::HIGH_SCHOOL => 'High school'];

    /** The owner's decision (Action Plan, Decisions: Age stages), by grade code. */
    public const DEFAULTS = [
        'R' => self::EARLY_YEARS, '1' => self::EARLY_YEARS, '2' => self::EARLY_YEARS,
        '3' => self::JUNIOR, '4' => self::JUNIOR, '5' => self::JUNIOR, '6' => self::JUNIOR, '7' => self::JUNIOR,
        '8' => self::HIGH_SCHOOL, '9' => self::HIGH_SCHOOL, '10' => self::HIGH_SCHOOL, '11' => self::HIGH_SCHOOL, '12' => self::HIGH_SCHOOL,
    ];

    public const PERMISSION = 'setting-create';

    public const SIGN_IN_REFUSED = 'Learners in the early years do not sign in themselves. A parent or guardian can follow their learning in the parents\' app.';

    private array $jobMaps = [];

    /** A school leader: Super Admin, or staff (not a family member or teacher) who manage school settings. */
    public static function mayManage(?User $user): bool
    {
        return $user !== null && ($user->hasRole('Super Admin') || (! $user->hasAnyRole(['Parent', 'Student', 'Teacher']) && $user->can(self::PERMISSION)));
    }

    /**
     * Whether a module's configured stages let a learner in this stage use it.
     * A module without `stages`, or a learner without a stage, is not limited.
     */
    public static function moduleAllows(string $module, ?string $stage): bool
    {
        $stages = config('modules.'.$module.'.stages');

        return ! is_array($stages) || $stage === null || in_array($stage, $stages, true);
    }

    /** @return array<int, string> grade_level_id => stage at the current school */
    public function map(): array
    {
        $school = app(CurrentSchool::class)->id();
        $cacheKey = 'stages.school.'.$school;
        if (RequestContext::inHttpRequest() && request()->attributes->has($cacheKey)) {
            return request()->attributes->get($cacheKey);
        }
        if (! RequestContext::inHttpRequest() && isset($this->jobMaps[$school])) {
            return $this->jobMaps[$school];
        }
        $map = [];
        foreach (GradeLevel::query()->pluck('code', 'id') as $id => $code) {
            if (isset(self::DEFAULTS[$code])) {
                $map[(int) $id] = self::DEFAULTS[$code];
            }
        }
        foreach (SchoolStage::query()->pluck('stage', 'grade_level_id') as $grade => $stage) {
            $map[(int) $grade] = (string) $stage;
        }
        if (RequestContext::inHttpRequest()) {
            request()->attributes->set($cacheKey, $map);
        }

        return $this->jobMaps[$school] = $map;
    }

    public function forget(): void
    {
        $school = app(CurrentSchool::class)->id();
        unset($this->jobMaps[$school]);
        if (app()->bound('request')) {
            request()->attributes->remove('stages.school.'.$school);
        }
    }

    public function forGrade(?int $gradeLevelId): ?string
    {
        return $gradeLevelId === null ? null : ($this->map()[$gradeLevelId] ?? null);
    }

    /** The stage of the learner's homeroom grade this school year, or their recorded class when there is none. */
    public function forLearner(Students $learner): ?string
    {
        $year = (int) schoolSetting('session_year');
        $section = StudentSessions::query()->where('student_id', $learner->id)->where('session_year_id', $year)->value('class_section_id');
        $class = $section !== null ? ClassSection::query()->whereKey($section)->value('class_id') : $learner->class_id;
        $grade = $class !== null ? ClassSchool::query()->whereKey($class)->value('grade_level_id') : null;

        return $this->forGrade($grade !== null ? (int) $grade : null);
    }

    /** The stage of a learner account at the current school; null for anyone else. */
    public function forUser(User $user): ?string
    {
        $cacheKey = 'stages.learner.'.app(CurrentSchool::class)->id().'.'.$user->id;
        if (RequestContext::inHttpRequest() && request()->attributes->has($cacheKey)) {
            return request()->attributes->get($cacheKey);
        }
        $learner = $user->hasRole('Student') ? Students::query()->where('user_id', $user->id)->first() : null;
        $stage = $learner === null ? null : $this->forLearner($learner);
        if (RequestContext::inHttpRequest()) {
            request()->attributes->set($cacheKey, $stage);
        }

        return $stage;
    }

    /** Early-years learners never sign in themselves; their guardians do. */
    public function assertMaySignIn(User $user): void
    {
        abort_if($this->forUser($user) === self::EARLY_YEARS, 403, __(self::SIGN_IN_REFUSED));
    }

    /**
     * Every grade with its stage, for the settings page.
     *
     * @return list<array{grade_level_id: int, grade: string, phase: string, stage: string, default: string}>
     */
    public function overview(User $actor): array
    {
        $this->authorise($actor);
        $map = $this->map();

        return GradeLevel::query()->orderBy('sort_order')->get()
            ->filter(fn (GradeLevel $grade) => isset($map[(int) $grade->id]))
            ->map(fn (GradeLevel $grade) => [
                'grade_level_id' => (int) $grade->id,
                'grade' => (string) $grade->name,
                'phase' => (string) $grade->phase,
                'stage' => $map[(int) $grade->id],
                'default' => self::DEFAULTS[$grade->code],
            ])->values()->all();
    }

    /**
     * Saves the stages a school leader chose. Only grades whose stage changes
     * are written; each write is audited.
     *
     * @param  array<int|string, string>  $stages  grade_level_id => stage
     * @return int how many grades changed stage
     */
    public function update(User $actor, array $stages): int
    {
        $this->authorise($actor);
        $grades = GradeLevel::query()->pluck('code', 'id');
        foreach ($stages as $grade => $stage) {
            if (! ctype_digit((string) $grade) || ! isset($grades[(int) $grade]) || ! isset(self::DEFAULTS[$grades[(int) $grade]])) {
                throw ValidationException::withMessages(['stages' => __('Choose a stage for a known grade.')]);
            }
            if (! is_string($stage) || ! array_key_exists($stage, self::STAGES)) {
                throw ValidationException::withMessages(['stages.'.$grade => __('Choose early years, junior or high school.')]);
            }
        }

        return DB::transaction(function () use ($actor, $stages): int {
            $school = app(CurrentSchool::class)->id();
            $rows = SchoolStage::query()->lockForUpdate()->get()->keyBy('grade_level_id');
            $this->forget();
            $current = $this->map();
            $changed = 0;
            foreach ($stages as $grade => $stage) {
                $grade = (int) $grade;
                if (($current[$grade] ?? null) === $stage) {
                    continue;
                }
                $row = $rows->get($grade) ?? new SchoolStage(['grade_level_id' => $grade]);
                $row->fill(['stage' => $stage, 'changed_by' => $actor->id])->save();
                $changed++;
            }
            $this->forget();
            if ($changed > 0) {
                Outbox::record(new ContextChanged($school, 'stages'));
            }

            return $changed;
        });
    }

    /** Fills a new school's mapping with the defaults. Not audited: nobody chose them. */
    public function seedDefaults(int $schoolId): void
    {
        $now = now();
        $have = DB::table('school_stages')->where('school_id', $schoolId)->pluck('grade_level_id')->map(fn ($id) => (int) $id)->all();
        $rows = [];
        foreach (GradeLevel::query()->pluck('id', 'code') as $code => $id) {
            if (isset(self::DEFAULTS[$code]) && ! in_array((int) $id, $have, true)) {
                $rows[] = ['school_id' => $schoolId, 'grade_level_id' => $id, 'stage' => self::DEFAULTS[$code], 'created_at' => $now, 'updated_at' => $now];
            }
        }
        if ($rows !== []) {
            DB::table('school_stages')->insert($rows);
        }
    }

    private function authorise(User $actor): void
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools(), 403);
        abort_unless(self::mayManage($actor), 403);
    }
}
