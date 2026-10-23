<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\BackfillPeopleIdentity;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\People\EnrolmentService;
use App\Services\People\RemoveLearner;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class EnrolmentHistoryTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    public function test_numbers_use_the_documented_checksum_and_reject_typing_errors(): void
    {
        $this->assertSame('4', StudentNumbers::checkDigit('260001234'));
        $this->assertTrue(StudentNumbers::valid('2600012344'));
        $this->assertFalse(StudentNumbers::valid('2600012347'));
        $this->assertFalse(StudentNumbers::valid('not-a-number'));
    }

    public function test_number_is_assigned_once_and_hidden_from_legacy_model_output(): void
    {
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        $service = app(StudentNumbers::class);
        $number = $service->assign($learner);
        $this->assertTrue(StudentNumbers::valid($number));
        $this->assertSame($number, $service->assign($learner));
        $this->assertArrayNotHasKey('student_number', $learner->fresh()->toArray());
        $this->assertSame(1, DB::table('learner_identifiers')->where('user_id', $learner->user_id)->count());
    }

    public function test_numbers_are_unique_across_schools(): void
    {
        $first = app(StudentNumbers::class)->assign(Students::findOrFail($this->schoolA->ids['asha']));
        $second = app(CurrentSchool::class)->run(2, fn () => app(StudentNumbers::class)->assign(Students::findOrFail($this->schoolB->ids['asha'])));
        $this->assertNotSame($first, $second);
        $this->assertTrue(StudentNumbers::valid($second));
    }

    public function test_same_human_keeps_the_number_when_enrolling_at_a_second_school(): void
    {
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        $number = app(StudentNumbers::class)->assign($learner);
        $attributes = $learner->getAttributes();
        unset($attributes['id'], $attributes['student_number']);
        app(CurrentSchool::class)->run(2, function () use ($attributes, $learner, $number) {
            $user = User::acrossSchools()->findOrFail($learner->user_id);
            $user->assignRole('Student');
            $copy = Students::query()->forceCreate(array_replace($attributes, [
                'school_id' => 2, 'class_section_id' => $this->schoolB->ids['section_a'],
                'category_id' => $this->schoolB->ids['category'], 'father_id' => null, 'mother_id' => null, 'guardian_id' => null,
            ]));
            $this->assertSame($number, $copy->getAttribute('student_number'));
        });
        $this->assertSame(1, DB::table('learner_identifiers')->where('user_id', $learner->user_id)->count());
    }

    public function test_removing_a_history_free_school_record_preserves_the_other_school_and_number(): void
    {
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        $number = app(StudentNumbers::class)->assign($learner);
        $attributes = $learner->getAttributes();
        unset($attributes['id'], $attributes['student_number']);
        $copy = app(CurrentSchool::class)->run(2, function () use ($attributes, $learner) {
            User::acrossSchools()->findOrFail($learner->user_id)->assignRole('Student');

            return Students::query()->forceCreate(array_replace($attributes, [
                'school_id' => 2, 'class_section_id' => $this->schoolB->ids['section_a'],
                'category_id' => $this->schoolB->ids['category'], 'father_id' => null, 'mother_id' => null, 'guardian_id' => null,
            ]));
        });
        app(RemoveLearner::class)->permanently($learner);
        $this->assertNull(Students::withTrashed()->find($learner->id));
        $this->assertFalse(User::acrossSchools()->withTrashed()->findOrFail($learner->user_id)->trashed());
        $this->assertSame($number, Students::acrossSchools()->findOrFail($copy->id)->student_number);
    }

    public function test_dated_history_blocks_permanent_cleanup_before_any_records_are_removed(): void
    {
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        app(EnrolmentService::class)->place($learner->id, $this->schoolA->ids['section_a'], $this->schoolA->ids['session_year'], now()->subMonth()->toDateString(), 'Retained enrolment');
        $this->actingAs($this->superAdmin())->deleteJson('/permanent-delete/'.$learner->id)
            ->assertJsonPath('error', true)->assertJsonPath('message', 'This learner has enrolment history. Archive the learner instead; retained records require the retention process.');
        $this->assertNotNull($learner->fresh());
        $this->assertSame(1, Enrolment::query()->where('learner_id', $learner->id)->count());
        $this->assertNotNull(StudentSessions::query()->where('student_id', $learner->id)->first());
    }

    public function test_context_contains_own_children_across_schools_without_including_unrelated_children(): void
    {
        $guardian = User::findOrFail($this->schoolA->ids['farid_user']);
        app(CurrentSchool::class)->run(2, function () use ($guardian) {
            $guardian->assignRole('Parent');
            GuardianLink::query()->create([
                'learner_id' => $this->schoolB->ids['cara'], 'guardian_user_id' => $guardian->id, 'relationship' => 'other',
            ]);
        });
        $answer = $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/v2/me/context')->assertOk();
        $children = $answer->json('data.children');
        $this->assertSame([$this->schoolA->ids['asha'], $this->schoolA->ids['cara'], $this->schoolB->ids['cara']], array_column($children, 'id'));
        $this->assertSame(2, $children[2]['school']['id']);
        $this->assertStringContainsString('Baobab', $children[2]['name']);
    }

    public function test_mid_year_move_preserves_the_previous_homeroom_and_is_idempotent(): void
    {
        $service = app(EnrolmentService::class);
        $start = now()->subDays(30)->toDateString();
        $move = now()->subDays(10)->toDateString();
        $first = $service->place($this->schoolA->ids['asha'], $this->schoolA->ids['section_a'], $this->schoolA->ids['session_year'], $start, 'First enrolment');
        $second = $service->place($this->schoolA->ids['asha'], $this->schoolA->ids['section_c'], $this->schoolA->ids['session_year'], $move, 'Moved homeroom');
        $this->assertSame($first->id, $second->previous_enrolment_id);
        $this->assertSame($this->schoolA->ids['section_a'], $service->classOn($this->schoolA->ids['asha'], $start));
        $this->assertSame($this->schoolA->ids['section_c'], $service->classOn($this->schoolA->ids['asha'], $move));
        $this->assertSame($second->id, $service->place($this->schoolA->ids['asha'], $this->schoolA->ids['section_c'], $this->schoolA->ids['session_year'], $move, 'Retry')->id);
        $this->assertSame(2, Enrolment::query()->where('learner_id', $this->schoolA->ids['asha'])->count());
    }

    public function test_scheduled_move_does_not_change_todays_legacy_projection(): void
    {
        $service = app(EnrolmentService::class);
        $service->place($this->schoolA->ids['asha'], $this->schoolA->ids['section_a'], $this->schoolA->ids['session_year'], now()->subMonth()->toDateString(), 'Current class');
        $service->place($this->schoolA->ids['asha'], $this->schoolA->ids['section_c'], $this->schoolA->ids['session_year'], now()->addDays(5)->toDateString(), 'Scheduled move');
        $this->assertSame($this->schoolA->ids['section_a'], (int) StudentSessions::query()->where('student_id', $this->schoolA->ids['asha'])->value('class_section_id'));
        $this->travel(6)->days();
        $service->syncProjection($this->schoolA->ids['asha'], $this->schoolA->ids['session_year']);
        $this->assertSame($this->schoolA->ids['section_c'], (int) StudentSessions::query()->where('student_id', $this->schoolA->ids['asha'])->value('class_section_id'));
    }

    public function test_legacy_class_edits_capture_the_old_assignment_before_appending_the_move(): void
    {
        $session = StudentSessions::query()->where('student_id', $this->schoolA->ids['asha'])->firstOrFail();
        $session->update(['class_section_id' => $this->schoolA->ids['section_c']]);
        $service = app(EnrolmentService::class);
        $this->assertSame($this->schoolA->ids['section_a'], $service->classOn($this->schoolA->ids['asha'], now()->subDay()->toDateString()));
        $this->assertSame($this->schoolA->ids['section_c'], $service->classOn($this->schoolA->ids['asha'], now()->toDateString()));
    }

    public function test_wrong_school_homeroom_is_refused_without_changing_the_projection(): void
    {
        try {
            app(EnrolmentService::class)->place($this->schoolA->ids['asha'], $this->schoolB->ids['section_a'], $this->schoolA->ids['session_year'], now()->toDateString(), 'Bad move');
            $this->fail('Cross-school class accepted');
        } catch (ModelNotFoundException) {
            $this->assertSame(0, Enrolment::query()->count());
        }
    }

    public function test_backfill_can_be_retried_without_fabricating_extra_moves(): void
    {
        $job = new BackfillPeopleIdentity(1);
        app()->call([$job, 'handle']);
        $count = Enrolment::query()->count();
        app()->call([$job, 'handle']);
        $this->assertSame($count, Enrolment::query()->count());
        $this->assertGreaterThan(0, $count);
        $this->assertTrue(Enrolment::query()->firstOrFail()->legacy_dates);
        $this->assertSame(3, DB::table('learner_identifiers')->count());
    }

    public function test_staff_can_append_and_read_a_dated_enrolment_through_the_contract(): void
    {
        $token = $this->superAdmin()->createToken('enrolment-staff')->plainTextToken;
        $path = '/api/v2/staff/learners/'.$this->schoolA->ids['asha'].'/enrolments';
        $this->withToken($token)->postJson($path, [
            'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->schoolA->ids['session_year'],
            'starts_on' => now()->subDays(10)->toDateString(), 'reason' => 'School admission',
        ])->assertOk()->assertJsonPath('data.learner_id', $this->schoolA->ids['asha']);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson($path.'?date='.now()->toDateString())->assertOk()->assertJsonPath('data.pagination.total', 1);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v2/staff/learners/'.$this->schoolB->ids['asha'].'/enrolments')->assertNotFound();
    }

    public function test_parent_and_teacher_cannot_move_another_persons_learner(): void
    {
        $path = '/api/v2/staff/learners/'.$this->schoolA->ids['asha'].'/enrolments';
        $body = ['class_section_id' => $this->schoolA->ids['section_c'], 'session_year_id' => $this->schoolA->ids['session_year'], 'starts_on' => now()->toDateString(), 'reason' => 'Unauthorised move'];
        $this->withToken($this->schoolA->tokens['farid'])->postJson($path, $body)->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['tara'])->postJson($path, $body)->assertForbidden();
    }

    public function test_a_student_number_signs_in_without_removing_the_legacy_login(): void
    {
        $number = app(StudentNumbers::class)->assign(Students::findOrFail($this->schoolA->ids['asha']));
        $this->postJson('/api/student/login', ['gr_number' => $number, 'password' => 'secret123'])->assertOk()->assertJsonPath('error', false);
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/student/login', ['gr_number' => 'GR-ASHA', 'password' => 'secret123'])->assertOk()->assertJsonPath('error', false);
    }
}
