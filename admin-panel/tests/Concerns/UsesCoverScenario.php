<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TimetableLesson;
use App\Models\ClassSchool;
use App\Models\Pastoral\CoverAbsence;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Pastoral\ReliefCover;
use App\Services\Platform\Tenancy\SchoolTable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait UsesCoverScenario
{
    use UsesTwoSchools;

    protected int $year;

    protected int $teacher;

    protected int $substitute;

    protected TimetableLesson $lesson;

    protected function setUpCoverScenario(): void
    {
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 07:50:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        config(['modules.relief_cover' => ['always_on' => true]]);
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Term::whereKey($this->schoolA->ids['term'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => GradeLevel::where('code', '7')->value('id')]);
        $this->teacher = (int) Teacher::where('user_id', $this->schoolA->ids['tara_user'])->value('id');
        $this->substitute = (int) Teacher::where('user_id', $this->schoolA->ids['theo_user'])->value('id');
        $group = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Cover maths', 'subject_id' => $this->schoolA->ids['maths'],
            'grade_level_id' => GradeLevel::where('code', '7')->value('id'), 'session_year_id' => $this->year,
            'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => $this->teacher, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Cover test allocation'], $group->id);
        $room = app(TimetableConfiguration::class)->room($this->superAdmin(), ['name' => 'Cover room', 'capacity' => 35, 'type' => 'classroom', 'active' => true]);
        $period = app(TimetableConfiguration::class)->bells($this->superAdmin(), ['name' => 'Cover bells', 'section' => 'Senior', 'session_year_id' => $this->year,
            'periods' => [['name' => 'P1', 'starts_at' => '08:00', 'ends_at' => '08:45', 'is_break' => false]]])->periods->sole();
        // The new feature intentionally refuses unmapped legacy placements. This
        // fixture represents a school whose old rows have already been mapped.
        SchoolTable::query('timetables')->delete();
        $this->lesson = TimetableLesson::create(['session_year_id' => $this->year, 'term_id' => $this->schoolA->ids['term'],
            'teaching_group_id' => $group->id, 'class_section_id' => null, 'teacher_id' => $this->teacher, 'room_id' => $room->id,
            'bell_period_id' => $period->id, 'starts_on' => '2026-02-10', 'ends_on' => '2026-02-17', 'occurs_on' => null,
            'weekday' => 2, 'week_pattern' => 'all', 'active' => true, 'revision' => 1])->refresh();
    }

    protected function person(string $name = 'tara'): User
    {
        return User::findOrFail($this->schoolA->ids[$name.'_user']);
    }

    protected function input(): array
    {
        return ['session_year_id' => $this->year, 'teacher_id' => $this->teacher, 'starts_on' => '2026-02-10', 'ends_on' => '2026-02-17', 'reason' => 'Private cover reason'];
    }

    protected function request(): CoverAbsence
    {
        return app(ReliefCover::class)->request($this->person(), $this->input(), (string) Str::uuid());
    }

    protected function refused(callable $effect, int $status): void
    {
        try {
            $effect();
            $this->fail('Expected a cover refusal.');
        } catch (HttpException $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }
}
