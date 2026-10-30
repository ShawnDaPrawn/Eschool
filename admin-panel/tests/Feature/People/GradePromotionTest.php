<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\People\EnrolmentService;
use App\Services\People\GradePromotion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Bulk promotion by grade (Action Plan 1.18): Grade 5 A (Asha, Ben) moves to
 * Grade 6 A by default; Cara's homeroom is in the last grade, so she leaves.
 */
class GradePromotionTest extends TestCase
{
    use UsesTwoSchools;

    private SessionYear $from;

    private SessionYear $to;

    private int $nextRoom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $ids = $this->schoolA->ids;
        $this->from = SessionYear::query()->findOrFail($ids['session_year']);
        $this->to = SessionYear::query()->forceCreate([
            'name' => 'Next year', 'start_date' => now()->addDays(250)->toDateString(), 'end_date' => now()->addDays(600)->toDateString(), 'default' => 0,
        ]);
        $grade = fn (int $order) => (int) DB::table('grade_levels')->where('sort_order', $order)->value('id');
        ClassSchool::query()->whereKey($ids['class_a'])->update(['grade_level_id' => $grade(5)]);
        ClassSchool::query()->whereKey($ids['class_c'])->update(['grade_level_id' => $grade(12)]);
        $medium = ClassSchool::query()->findOrFail($ids['class_a'])->medium_id;
        $grade6 = ClassSchool::query()->forceCreate(['name' => 'Grade 6', 'medium_id' => $medium, 'grade_level_id' => $grade(6)]);
        $sectionA = ClassSection::query()->findOrFail($ids['section_a'])->section_id;
        $this->nextRoom = (int) ClassSection::query()->forceCreate(['class_id' => $grade6->id, 'section_id' => $sectionA])->id;
        $start = (string) $this->from->start_date;
        foreach (['asha' => 'section_a', 'ben' => 'section_a', 'cara' => 'section_c'] as $learner => $room) {
            app(EnrolmentService::class)->place($ids[$learner], $ids[$room], $ids['session_year'], $start, 'Start of year');
        }
    }

    public function test_default_plan_moves_each_homeroom_to_the_same_letter_in_the_next_grade(): void
    {
        $plan = app(GradePromotion::class)->plan($this->from, $this->to);
        $rows = $this->rows($plan);

        $this->assertSame(['promote', $this->nextRoom], [$rows[$this->schoolA->ids['asha']]['outcome'], $rows[$this->schoolA->ids['asha']]['target']]);
        $this->assertSame('promote', $rows[$this->schoolA->ids['ben']]['outcome']);
        $this->assertSame(['leave', null], [$rows[$this->schoolA->ids['cara']]['outcome'], $rows[$this->schoolA->ids['cara']]['target']]);
        $this->assertSame(['promote' => 2, 'repeat' => 0, 'leave' => 1, 'place' => 0, 'already' => 0], $plan['totals']);
        $this->assertSame(0, $plan['unresolved']);
        $this->assertStringNotContainsString(self::SCHOOL_B_MARKER, json_encode($plan));
    }

    public function test_committing_writes_dated_enrolments_and_the_legacy_rows(): void
    {
        $service = app(GradePromotion::class);
        $plan = $service->plan($this->from, $this->to);
        $done = $service->commit($this->superAdmin(), $this->from, $this->to, [], $plan['fingerprint']);
        $ids = $this->schoolA->ids;

        $this->assertSame(['promote' => 2, 'repeat' => 0, 'leave' => 1, 'place' => 0], $done);
        $next = Enrolment::query()->where('session_year_id', $this->to->id)->where('learner_id', $ids['asha'])->firstOrFail();
        $this->assertSame([$this->nextRoom, (string) $this->to->start_date], [(int) $next->class_section_id, $next->starts_on->toDateString()]);
        $this->assertSame(0, Enrolment::query()->where('session_year_id', $this->to->id)->where('learner_id', $ids['cara'])->count());
        // This year's homeroom is unchanged: it still runs to the year's last day.
        $now = Enrolment::query()->where('session_year_id', $this->from->id)->where('learner_id', $ids['asha'])->firstOrFail();
        $this->assertSame([$ids['section_a'], (string) $this->from->end_date], [(int) $now->class_section_id, $now->ends_on->toDateString()]);

        $asha = StudentSessions::query()->where('student_id', $ids['asha'])->where('session_year_id', $this->to->id)->firstOrFail();
        $this->assertSame([$this->nextRoom, 1, 1, (int) $this->from->id], [(int) $asha->class_section_id, (int) $asha->status, (int) $asha->result, (int) $asha->previous_session_year_id]);
        $cara = StudentSessions::query()->where('student_id', $ids['cara'])->where('session_year_id', $this->to->id)->firstOrFail();
        $this->assertSame([null, 0], [$cara->class_section_id, (int) $cara->status]);
        $this->assertTrue(DB::table('audit_log')->where('action', 'people.grade_promotion_committed')->exists());

        // Everyone is now placed, so a second run changes nothing.
        $again = $service->plan($this->from, $this->to);
        $this->assertSame(3, $again['totals']['already']);
        $this->assertSame(['promote' => 0, 'repeat' => 0, 'leave' => 0, 'place' => 0], $service->commit($this->superAdmin(), $this->from, $this->to, [], $again['fingerprint']));
    }

    public function test_exceptions_repeat_and_place_learners(): void
    {
        $ids = $this->schoolA->ids;
        $service = app(GradePromotion::class);
        $choices = [
            $ids['ben'] => ['outcome' => GradePromotion::REPEAT],
            $ids['cara'] => ['outcome' => GradePromotion::PLACE, 'homeroom_id' => $this->nextRoom],
        ];
        $plan = $service->plan($this->from, $this->to, $choices);
        $service->commit($this->superAdmin(), $this->from, $this->to, $choices, $plan['fingerprint']);

        $ben = StudentSessions::query()->where('student_id', $ids['ben'])->where('session_year_id', $this->to->id)->firstOrFail();
        $this->assertSame([$ids['section_a'], 1, 0], [(int) $ben->class_section_id, (int) $ben->status, (int) $ben->result]);
        $this->assertSame($this->nextRoom, (int) Enrolment::query()->where('session_year_id', $this->to->id)->where('learner_id', $ids['cara'])->value('class_section_id'));
    }

    public function test_a_pasted_sheet_names_learners_by_admission_number_and_homerooms_by_name(): void
    {
        $ids = $this->schoolA->ids;
        $number = fn (string $learner) => (string) DB::table('students')->where('id', $ids[$learner])->value('admission_no');
        $sheet = "admission_no,outcome,homeroom\n{$number('ben')}, repeat\n{$number('cara')},place,grade 6  a\nNOPE-1,promote\n{$number('asha')},stay\n{$number('asha')},place,Grade 9 Z";
        $parsed = app(GradePromotion::class)->parseSheet($this->from, $sheet);

        $this->assertSame([
            $ids['ben'] => ['outcome' => 'repeat', 'homeroom_id' => null],
            $ids['cara'] => ['outcome' => 'place', 'homeroom_id' => $this->nextRoom],
        ], $parsed['exceptions']);
        $this->assertCount(3, $parsed['errors']);
        $this->assertStringContainsString('NOPE-1', $parsed['errors'][0]);
    }

    public function test_changes_since_the_preview_and_missing_homerooms_are_refused(): void
    {
        $service = app(GradePromotion::class);
        $plan = $service->plan($this->from, $this->to);
        // Someone places Asha meanwhile: the preview is stale.
        app(EnrolmentService::class)->place($this->schoolA->ids['asha'], $this->nextRoom, (int) $this->to->id, (string) $this->to->start_date, 'By hand');
        try {
            $service->commit($this->superAdmin(), $this->from, $this->to, [], $plan['fingerprint']);
            $this->fail('A stale preview was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fingerprint', $e->errors());
        }
        $this->assertSame('placed', $this->rows($service->plan($this->from, $this->to))[$this->schoolA->ids['asha']]['already']);

        // With no Grade 6 A, Grade 5 A has nowhere to go.
        ClassSection::query()->whereKey($this->nextRoom)->delete();
        $plan = $service->plan($this->from, $this->to);
        $this->assertSame(1, $plan['unresolved']);
        $this->expectException(ValidationException::class);
        $service->commit($this->superAdmin(), $this->from, $this->to, [], $plan['fingerprint']);
    }

    public function test_promoting_into_a_year_that_has_not_started_after_this_one_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        app(GradePromotion::class)->plan($this->to, $this->from);
    }

    public function test_panel_checks_then_promotes(): void
    {
        $ids = $this->schoolA->ids;
        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('/promotions/by-grade')->assertOk()
            ->assertSee('Next year')->assertSee('Grade 5 A to Grade 6 A')->assertDontSee(self::SCHOOL_B_MARKER);

        $form = ['from' => $this->from->id, 'to' => $this->to->id, 'complete' => 1, 'learners' => [$ids['ben'] => ['outcome' => 'repeat']]];
        $check = $this->actingAs($admin)->post('/promotions/by-grade/check', $form)->assertOk()->assertSee('1 promoted, 1 repeating, 0 placed, 1 leaving.', false);
        preg_match('/name="fingerprint" value="([0-9a-f]{64})"/', $check->getContent(), $match);

        $this->actingAs($admin)->post('/promotions/by-grade', $form + ['fingerprint' => $match[1]])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame(0, (int) StudentSessions::query()->where('student_id', $ids['ben'])->where('session_year_id', $this->to->id)->value('result'));

        // A form cut short by the server is refused rather than half-applied.
        $this->actingAs($admin)->post('/promotions/by-grade', ['from' => $this->from->id, 'to' => $this->to->id, 'fingerprint' => $match[1]])
            ->assertStatus(422)->assertSee('Too many choices arrived at once');
    }

    public function test_staff_without_promotion_permissions_cannot_use_it(): void
    {
        $teacher = User::query()->findOrFail($this->schoolA->ids['tara_user']);
        $this->assertFalse($teacher->can('promote-student-list'));
        $this->actingAs($teacher)->get('/promotions/by-grade')->assertForbidden();
        $this->actingAs($teacher)->post('/promotions/by-grade', ['from' => $this->from->id, 'to' => $this->to->id])->assertForbidden();
        $this->assertSame(0, Enrolment::query()->where('session_year_id', $this->to->id)->count());
    }

    /** @return array<int, array<string, mixed>> learner id => row */
    private function rows(array $plan): array
    {
        $rows = [];
        foreach ($plan['homerooms'] as $room) {
            foreach ($room['learners'] as $row) {
                $rows[$row['id']] = $row;
            }
        }

        return $rows;
    }
}
