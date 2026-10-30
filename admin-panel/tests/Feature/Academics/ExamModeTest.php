<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\Academics\ExamPeriod;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\ExamTimetable;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\ExamMode;
use App\Services\Academics\TeachingGroups;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Academics\TimetableEngine;
use App\Services\Academics\TimetableQueries;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * Exam mode: per-grade exam periods turn a learner's timetable days into exam
 * days, and a changed or removed upcoming sitting tells the class and its
 * guardians. Specification: engineering/specs/academics/exam-mode.md.
 */
class ExamModeTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $year;

    private int $grade;

    private int $sitting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 07:50:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Exam mode rehearsal']);
        $this->weeklyLesson();
        $this->sitting = (int) DB::table('exam_timetables')->insertGetId([
            'school_id' => 1, 'exam_id' => $this->schoolA->ids['exam'], 'class_id' => $this->schoolA->ids['class_a'], 'subject_id' => $this->schoolA->ids['maths'],
            'total_marks' => 100, 'passing_marks' => 35, 'date' => '2026-02-24', 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'session_year_id' => $this->year,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** One Maths lesson every Tuesday for Asha's class. */
    private function weeklyLesson(): void
    {
        $admin = $this->superAdmin();
        $teacher = (int) Teacher::where('user_id', $this->schoolA->ids['tara_user'])->value('id');
        $groups = app(TeachingGroups::class);
        $group = $groups->save($admin, ['name' => 'Maths 7A', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $dates = ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Exam mode allocation'];
        $groups->dated($admin, 'teachers', $dates + ['teacher_id' => $teacher], $group->id);
        $groups->dated($admin, 'memberships', $dates + ['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up'], $group->id);
        $room = app(TimetableConfiguration::class)->room($admin, ['name' => 'Room 1', 'capacity' => 35, 'type' => 'classroom', 'active' => true]);
        $bells = app(TimetableConfiguration::class)->bells($admin, ['name' => 'Senior', 'section' => 'Senior', 'session_year_id' => $this->year,
            'periods' => [['name' => 'P1', 'starts_at' => '07:00', 'ends_at' => '07:40', 'is_break' => false]]])->load('periods');
        $engine = app(TimetableEngine::class);
        $preview = $engine->preview($admin, [['session_year_id' => $this->year, 'term_id' => null, 'teaching_group_id' => $group->id, 'class_section_id' => null,
            'teacher_id' => $teacher, 'room_id' => $room->id, 'bell_period_id' => $bells->periods->first()->id, 'starts_on' => '2026-02-10', 'ends_on' => '2026-03-31',
            'weekday' => 2, 'week_pattern' => 'all', 'active' => true]]);
        $this->assertTrue($preview['can_save'], json_encode($preview['conflicts']));
        $engine->save($admin, $preview['rows'], $preview['fingerprint']);
    }

    private function period(array $changes = []): ExamPeriod
    {
        return app(ExamMode::class)->save($this->superAdmin(), $this->year, $changes + ['name' => 'Term 1 tests', 'grade_level_id' => $this->grade, 'starts_on' => '2026-02-23', 'ends_on' => '2026-02-27']);
    }

    private function week(?User $actor, string $starts, ?int $learner): array
    {
        return app(TimetableQueries::class)->week($actor ?? User::findOrFail($this->schoolA->ids['farid_user']), $this->year, $starts, $learner);
    }

    private function sitting(): ExamTimetable
    {
        return ExamTimetable::query()->findOrFail($this->sitting);
    }

    public function test_exam_days_replace_the_learners_lessons_unless_they_continue(): void
    {
        $this->assertSame(['2026-02-17'], array_column($this->week(null, '2026-02-16', $this->schoolA->ids['asha'])['lessons'], 'date'));
        $period = $this->period();

        $week = $this->week(null, '2026-02-23', $this->schoolA->ids['asha']);
        $this->assertSame(['2026-02-23', '2026-02-24', '2026-02-25', '2026-02-26', '2026-02-27'], $week['exam_days']);
        $this->assertSame([], $week['lessons'], 'the Tuesday lesson is left out on an exam day');
        $this->assertSame([], $this->week(null, '2026-02-16', $this->schoolA->ids['asha'])['exam_days'], 'other weeks are unchanged');

        app(ExamMode::class)->save($this->superAdmin(), $this->year, ['name' => 'Term 1 tests', 'grade_level_id' => $this->grade, 'starts_on' => '2026-02-23', 'ends_on' => '2026-02-27', 'lessons_continue' => true], $period);
        $week = $this->week(null, '2026-02-23', $this->schoolA->ids['asha']);
        $this->assertCount(5, $week['exam_days']);
        $this->assertSame(['2026-02-24'], array_column($week['lessons'], 'date'));

        // Another grade's period changes nothing for this learner; staff views carry no exam days.
        $period->update(['grade_level_id' => (int) GradeLevel::where('code', '8')->value('id')]);
        $this->assertSame([], $this->week(null, '2026-02-23', $this->schoolA->ids['asha'])['exam_days']);
        $this->assertSame([], $this->week($this->superAdmin(), '2026-02-23', null)['exam_days']);
    }

    public function test_periods_stay_inside_the_year_and_never_overlap_for_a_grade(): void
    {
        $this->period();
        foreach ([
            ['starts_on' => '2026-02-26', 'ends_on' => '2026-03-03'],
            ['starts_on' => '2026-03-05', 'ends_on' => '2026-03-04'],
            ['starts_on' => '2025-12-20', 'ends_on' => '2026-01-05'],
        ] as $dates) {
            try {
                $this->period($dates);
                $this->fail('Saved '.json_encode($dates));
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->period(['grade_level_id' => (int) GradeLevel::where('code', '8')->value('id')]);
        $this->assertSame(2, ExamPeriod::count());
    }

    public function test_a_changed_venue_or_time_tells_the_class_and_guardians_once(): void
    {
        $sitting = $this->sitting();
        $sitting->venue = 'School hall';
        $sitting->start_time = '09:30';
        $sitting->save();

        $this->assertSame(2, $sitting->fresh()->revision);
        $notice = Notification::where('type', ExamMode::NOTICE)->sole();
        $this->assertStringContainsString('School hall', $notice->message);
        $this->assertStringContainsString('09:30', $notice->message);
        $users = OutboxEvent::where('type', 'notification.requested')->where('payload->notification_id', $notice->id)->sole()->payload['user_ids'];
        foreach (['asha_user', 'farid_user'] as $person) {
            $this->assertContains((int) $this->schoolA->ids[$person], $users);
        }
        $this->assertNotContains((int) $this->schoolA->ids['tara_user'], $users);

        // The same time written another way, or a marks change, is not news.
        $sitting = $this->sitting();
        $sitting->start_time = '09:30:00';
        $sitting->total_marks = 80;
        $sitting->save();
        $this->assertSame(2, $sitting->fresh()->revision);
        $this->assertSame(1, Notification::where('type', ExamMode::NOTICE)->count());
    }

    public function test_past_sittings_change_quietly_and_a_removed_upcoming_one_is_announced(): void
    {
        DB::table('exam_timetables')->where('id', $this->sitting)->update(['date' => '2026-02-03']);
        $sitting = $this->sitting();
        $sitting->venue = 'Library';
        $sitting->save();
        $this->assertSame(0, Notification::where('type', ExamMode::NOTICE)->count());

        DB::table('exam_timetables')->where('id', $this->sitting)->update(['date' => '2026-02-24']);
        $this->sitting()->delete();
        $notice = Notification::where('type', ExamMode::NOTICE)->sole();
        $this->assertStringStartsWith('Exam removed', $notice->title);
        $this->assertSame('/calendar', $notice->delivery_payload['link'], 'a path the learner and parent app opens');
    }

    public function test_deleting_the_whole_exam_announces_each_upcoming_sitting(): void
    {
        DB::table('exam_timetables')->insert([
            'school_id' => 1, 'exam_id' => $this->schoolA->ids['exam'], 'class_id' => $this->schoolA->ids['class_a'], 'subject_id' => $this->schoolA->ids['maths'],
            'total_marks' => 100, 'passing_marks' => 35, 'date' => '2026-02-03', 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'session_year_id' => $this->year,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin())->deleteJson('/exams/'.$this->schoolA->ids['exam'])->assertOk()->assertJson(['error' => false]);

        $this->assertSame(0, ExamTimetable::where('exam_id', $this->schoolA->ids['exam'])->count());
        // The exam's upcoming sittings (two from the fixture, 24 Feb here) are announced; the past one is not.
        $notices = Notification::where('type', ExamMode::NOTICE)->orderBy('id')->get();
        $this->assertCount(3, $notices);
        $this->assertSame(1, $notices->filter(fn ($notice) => str_contains($notice->message, '24 Feb 2026'))->count());
        $this->assertSame(0, $notices->filter(fn ($notice) => str_contains($notice->message, 'on 3 Feb 2026'))->count());
        $notice = $notices->last();
        $this->assertStringStartsWith('Exam removed', $notice->title);
        $this->assertSame('/calendar', $notice->delivery_payload['link']);
        $users = OutboxEvent::where('type', 'notification.requested')->where('payload->notification_id', $notice->id)->sole()->payload['user_ids'];
        $this->assertContains((int) $this->schoolA->ids['farid_user'], $users);
    }

    public function test_the_exam_timetable_form_saves_a_venue_and_announces_the_change(): void
    {
        $sitting = $this->sitting();
        $this->actingAs($this->superAdmin())->postJson('/exams/update-timetable', [
            'exam_id' => $sitting->exam_id, 'class_id' => $sitting->class_id, 'session_year_id' => $this->year,
            'edit_timetable' => [['timetable_id' => $sitting->id, 'subject_id' => $sitting->subject_id, 'total_marks' => 100, 'passing_marks' => 35,
                'start_time' => '09:00', 'end_time' => '11:00', 'date' => '24-02-2026', 'venue' => '  Hall B  ']],
        ])->assertOk()->assertJson(['error' => false]);

        $this->assertSame('Hall B', $sitting->fresh()->venue);
        $this->assertSame(1, Notification::where('type', ExamMode::NOTICE)->count());
    }

    public function test_the_panel_page_manages_periods_for_its_own_school_only(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('/exam-periods')->assertOk()->assertSee('Add an exam period');
        $this->post('/exam-periods', ['session_year_id' => $this->year, 'name' => 'June exams', 'grade_level_id' => $this->grade, 'starts_on' => '2026-06-01', 'ends_on' => '2026-06-19'])
            ->assertRedirect()->assertSessionHas('success');
        $period = ExamPeriod::sole();
        $this->get('/exam-periods?session_year_id='.$this->year)->assertSee('June exams');
        $this->put('/exam-periods/'.$period->id, ['session_year_id' => $this->year, 'name' => 'June exams', 'grade_level_id' => $this->grade, 'starts_on' => '2026-06-02', 'ends_on' => '2026-06-19', 'lessons_continue' => '1'])
            ->assertRedirect();
        $this->assertTrue($period->fresh()->lessons_continue);
        $this->assertSame('2026-06-02', $period->fresh()->starts_on->toDateString());

        $other = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => ExamPeriod::create(['session_year_id' => $this->schoolB->ids['session_year'], 'grade_level_id' => $this->grade, 'name' => 'Other school', 'starts_on' => '2026-06-01', 'ends_on' => '2026-06-05']));
        $this->delete('/exam-periods/'.$other->id)->assertNotFound();
        $this->delete('/exam-periods/'.$period->id)->assertRedirect();
        $this->assertSame(0, ExamPeriod::count());

        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actingAs($teacher)->get('/exam-periods')->assertForbidden();
    }

    public function test_the_family_week_api_lists_exam_days_within_the_contract(): void
    {
        $this->period();
        $this->app['auth']->forgetGuards();
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);

        $data = $this->flushHeaders()->withToken($parent->createToken('exam-mode')->plainTextToken)
            ->getJson('/api/v2/academics/timetable/week?session_year_id='.$this->year.'&starts_on=2026-02-23&learner_id='.$this->schoolA->ids['asha'])
            ->assertOk()->json('data');

        $this->assertSame('2026-02-23', $data['exam_days'][0]);
        $this->assertSame([], $data['lessons']);
    }
}
