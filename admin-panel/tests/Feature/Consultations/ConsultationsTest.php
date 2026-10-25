<?php

declare(strict_types=1);

namespace Tests\Feature\Consultations;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityStaff;
use App\Models\Consultations\ConfigurationEvent;
use App\Models\Consultations\Consultation;
use App\Models\Consultations\Operation;
use App\Models\Consultations\Option;
use App\Models\Consultations\Participation;
use App\Models\Consultations\Question;
use App\Models\Consultations\Response;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Consultations\Consultations;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ConsultationsTest extends TestCase
{
    use UsesTwoSchools;

    private int $year;

    private User $learner;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T08:00:00Z'));
        Queue::fake();
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->learner = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        app(ModuleGate::class)->change('consultations', true, $this->superAdmin());
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year,
            'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic consultation cohort']);
    }

    private function op(array $input = []): array
    {
        return $input + ['operation_id' => (string) Str::uuid()];
    }

    private function data(array $extra = []): array
    {
        return $extra + $this->op(['revision' => 0, 'session_year_id' => $this->year, 'type' => 'poll', 'title' => 'Synthetic school choice', 'instructions' => 'Choose once.',
            'opens_at' => '2026-02-10T07:00:00Z', 'closes_at' => '2026-02-11T08:00:00Z', 'anonymous' => true, 'results_visibility' => 'after_close',
            'audience' => ['kind' => 'school', 'parents' => false], 'questions' => [['kind' => 'single', 'label' => 'Synthetic preference', 'required' => true,
                'options' => [['label' => 'Blue'], ['label' => 'Green']]]]]);
    }

    private function create(array $extra = [], bool $publish = true): Consultation
    {
        $service = app(Consultations::class);
        $receipt = $service->save($this->superAdmin(), null, $this->data($extra));
        if ($publish) {
            $service->publish($this->superAdmin(), $receipt['id'], $this->op(['revision' => $receipt['revision']]));
        }

        return Consultation::findOrFail($receipt['id']);
    }

    private function answers(Consultation $row): array
    {
        $q = Question::where('consultation_id', $row->id)->firstOrFail();

        return [['question_id' => (int) $q->id, 'option_ids' => [(int) Option::where('question_id', $q->id)->firstOrFail()->id]]];
    }

    private function refused(int $status, callable $work): void
    {
        try {
            $work();
            $this->fail('The action unexpectedly succeeded.');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }

    public function test_anonymous_vote_is_atomic_unique_encrypted_and_physically_separate_from_participation(): void
    {
        $row = $this->create();
        $answers = $this->answers($row);
        $events = ConfigurationEvent::count();
        $operations = Operation::count();
        $this->assertSame(['participated' => true], app(Consultations::class)->vote($this->learner, $row->id, $answers));
        $this->assertSame(1, Participation::count());
        $this->assertSame(1, Response::count());
        $this->assertNull(Response::sole()->respondent_user_id);
        $this->assertTrue(Str::isUuid(Response::sole()->id));
        $this->assertSame($events, ConfigurationEvent::count());
        $this->assertSame($operations, Operation::count());
        $this->assertSame([], OutboxEvent::where('type', 'consultations.counts_changed')->get()->all());
        $this->assertEqualsCanonicalizing(['school_id', 'consultation_id', 'user_id'], Schema::getColumnListing('consultation_participation'));
        $this->assertEqualsCanonicalizing(['id', 'school_id', 'consultation_id', 'respondent_user_id', 'answers'], Schema::getColumnListing('consultation_responses'));
        $raw = DB::table('consultation_responses')->first();
        $this->assertNotSame(json_encode($answers), $raw->answers);
        $this->refused(409, fn () => app(Consultations::class)->vote($this->learner, $row->id, $answers));
        $this->assertSame(1, Response::count());
        $this->assertTrue(app(Consultations::class)->ballot($this->learner, $row->id)['participated']);
    }

    public function test_database_one_person_constraint_refuses_a_second_writer(): void
    {
        $row = $this->create();
        app(Consultations::class)->vote($this->learner, $row->id, $this->answers($row));
        try {
            DB::table('consultation_participation')->insert(['school_id' => 1, 'consultation_id' => $row->id, 'user_id' => $this->learner->id]);
            $this->fail('Duplicate participation accepted');
        } catch (QueryException) {
            $this->assertSame(1, Participation::count());
        }
    }

    public function test_private_results_stay_hidden_from_managers_until_close_and_recount_exactly(): void
    {
        $row = $this->create();
        app(Consultations::class)->vote($this->learner, $row->id, $this->answers($row));
        $this->refused(403, fn () => app(Consultations::class)->results($this->superAdmin(), $row->id, true));
        $this->refused(403, fn () => app(Consultations::class)->results($this->learner, $row->id));
        $this->travelTo(CarbonImmutable::parse('2026-02-11T08:00:00Z'));
        $results = app(Consultations::class)->results($this->learner, $row->id);
        $this->assertTrue($results['closed']);
        $this->assertSame(1, $results['response_count']);
        $this->assertSame([1, 0], array_column($results['questions'][0]['counts'], 'count'));
        $this->assertArrayNotHasKey('user_id', $results);
        $this->assertArrayNotHasKey('texts', $results['questions'][0]);
        $this->refused(409, fn () => app(Consultations::class)->vote($this->superAdmin(), $row->id, $this->answers($row)));
    }

    public function test_published_configuration_questions_and_ballots_are_immutable_and_config_replay_stable(): void
    {
        $input = $this->data();
        $service = app(Consultations::class);
        $first = $service->save($this->superAdmin(), null, $input);
        $this->assertSame($first, $service->save($this->superAdmin(), null, $input));
        $this->refused(409, fn () => $service->save($this->superAdmin(), null, ['title' => 'Changed'] + $input));
        $service->publish($this->superAdmin(), $first['id'], $this->op(['revision' => 1]));
        $row = Consultation::findOrFail($first['id']);
        $this->refused(409, fn () => $service->save($this->superAdmin(), $row->id, $this->data(['revision' => $row->revision])));
        $this->refused(409, fn () => $row->update(['title' => 'Mutated title']));
        $this->refused(409, fn () => Question::where('consultation_id', $row->id)->firstOrFail()->update(['label' => 'Mutated question']));
        $this->assertNotEmpty(ConfigurationEvent::firstOrFail()->snapshot['questions']);
        app(Consultations::class)->vote($this->learner, $row->id, $this->answers($row));
        $this->refused(409, fn () => Response::sole()->update(['answers' => []]));
    }

    public function test_wrong_option_cannot_enter_another_consultation_or_leave_partial_participation(): void
    {
        $a = $this->create();
        $b = $this->create();
        $answer = $this->answers($a);
        $answer[0]['option_ids'] = $this->answers($b)[0]['option_ids'];
        $this->refused(422, fn () => app(Consultations::class)->vote($this->learner, $a->id, $answer));
        $this->assertSame(0, Participation::count());
        $this->assertSame(0, Response::count());
    }

    public function test_schedule_advances_once_and_never_sends_expired_opening_or_reopens_a_closed_ballot(): void
    {
        $row = $this->create(['opens_at' => '2026-02-10T09:00:00Z']);
        $service = app(Consultations::class);
        $this->assertSame('scheduled', $row->status);
        $this->assertSame(0, $service->advance());
        $this->refused(409, fn () => $service->vote($this->learner, $row->id, $this->answers($row)));
        $this->travelTo(CarbonImmutable::parse('2026-02-10T09:00:00Z'));
        $this->assertSame(1, $service->advance());
        $this->assertSame(0, $service->advance());
        $event = OutboxEvent::where('type', 'consultations.opened')->sole();
        $this->assertEqualsCanonicalizing(['consultation_id', 'revision'], array_keys($event->payload));
        $this->travelTo(CarbonImmutable::parse('2026-02-11T08:00:00Z'));
        $this->assertSame(1, $service->advance());
        $this->assertSame('closed', $row->fresh()->status);
        $this->assertSame(0, $service->advance());
        $this->assertNull(ConfigurationEvent::where('action', 'clock_opened')->sole()->actor_id);
    }

    public function test_readonly_keeps_reads_and_refuses_votes_and_configuration(): void
    {
        $row = $this->create();
        SchoolModule::where('module', 'consultations')->update(['read_only' => true]);
        $this->assertFalse(app(Consultations::class)->ballot($this->learner, $row->id)['participated']);
        $this->refused(403, fn () => app(Consultations::class)->vote($this->learner, $row->id, $this->answers($row)));
        $this->refused(403, fn () => app(Consultations::class)->save($this->superAdmin(), null, $this->data()));
    }

    public function test_fresh_role_password_platform_and_school_boundaries_apply_before_reads_or_replay(): void
    {
        $row = $this->create();
        $service = app(Consultations::class);
        $this->refused(403, fn () => $service->configuration($this->learner, $row->id));
        $this->refused(403, fn () => $service->configuration(User::findOrFail($this->schoolA->ids['tara_user']), $row->id));
        $this->learner->forceFill(['password_change_required' => true])->save();
        $this->refused(403, fn () => $service->ballot($this->learner, $row->id));
        $this->learner->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $this->learner->id, 'active' => true]);
        $this->refused(403, fn () => $service->ballot($this->learner, $row->id));
        PlatformStaffIdentity::where('user_id', $this->learner->id)->delete();
        app(CurrentSchool::class)->run(2, function () use ($row, $service) {
            SchoolModule::updateOrCreate(['module' => 'consultations'], ['enabled' => true, 'in_package' => true, 'read_only' => false]);
            try {
                $service->ballot(User::findOrFail($this->schoolB->ids['asha_user']), $row->id);
                $this->fail('Cross-school ballot read');
            } catch (ModelNotFoundException) {
                $this->assertTrue(true);
            }
        });
        SchoolMembership::where('school_id', 1)->where('user_id', $this->learner->id)->update(['status' => 'inactive']);
        $this->refused(403, fn () => $service->ballot($this->learner, $row->id));
    }

    public function test_parent_eligibility_is_removed_with_a_fresh_guardian_or_enrolment_withdrawal(): void
    {
        $row = $this->create(['audience' => ['kind' => 'school', 'parents' => true]]);
        $this->assertFalse(app(Consultations::class)->ballot($this->parent, $row->id)['participated']);
        GuardianLink::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id, 'relationship' => 'father', 'can_approve' => true, 'status' => 'ended']);
        $this->refused(404, fn () => app(Consultations::class)->ballot($this->parent, $row->id));
    }

    public function test_public_live_counts_have_no_actor_answer_or_response_reference(): void
    {
        $row = $this->create(['results_visibility' => 'public_live']);
        app(Consultations::class)->vote($this->learner, $row->id, $this->answers($row));
        $event = OutboxEvent::where('type', 'consultations.counts_changed')->sole();
        $this->assertSame(['consultation_id' => (int) $row->id, 'revision' => 1], $event->payload);
        $this->assertSame(1, app(Consultations::class)->results($this->learner, $row->id)['response_count']);
    }

    public function test_individual_survey_responses_are_paged_after_close_and_anonymous_rows_have_no_linking_fields(): void
    {
        foreach ([true, false] as $anonymous) {
            $row = $this->create(['type' => 'survey', 'anonymous' => $anonymous, 'results_visibility' => 'public_live',
                'questions' => [['kind' => 'text', 'label' => 'Optional feedback', 'required' => true]]]);
            $question = Question::where('consultation_id', $row->id)->sole();
            app(Consultations::class)->vote($this->learner, $row->id, [['question_id' => (int) $question->id, 'text' => 'Synthetic private answer']]);
            $this->assertArrayNotHasKey('texts', app(Consultations::class)->results($this->learner, $row->id)['questions'][0]);
            $this->refused(403, fn () => app(Consultations::class)->responses($this->superAdmin(), $row->id));
            app(Consultations::class)->end($this->superAdmin(), $row->id, 'close', $this->op(['revision' => $row->revision, 'reason' => 'Synthetic close']));
            $page = app(Consultations::class)->responses($this->superAdmin(), $row->id);
            $this->assertCount(1, $page['items']);
            $this->assertSame('Synthetic private answer', $page['items'][0]['answers'][0]['text']);
            if ($anonymous) {
                $this->assertNull($page['items'][0]['respondent_user_id']);
                $this->assertNull($page['items'][0]['respondent_name']);
            } else {
                $this->assertSame((int) $this->learner->id, $page['items'][0]['respondent_user_id']);
            }
            $this->assertArrayNotHasKey('id', $page['items'][0]);
            $this->assertArrayNotHasKey('created_at', $page['items'][0]);
            $this->refused(403, fn () => app(Consultations::class)->responses($this->learner, $row->id));
            $this->assertStringNotContainsString('Synthetic private answer', DB::table('consultation_responses')->where('consultation_id', $row->id)->value('answers'));
        }
    }

    public function test_anonymous_identity_is_refused_and_a_response_failure_rolls_back_participation(): void
    {
        $row = $this->create();
        $this->refused(422, fn () => Response::create(['id' => (string) Str::uuid(), 'consultation_id' => $row->id, 'respondent_user_id' => $this->learner->id, 'answers' => []]));
        $fail = true;
        Response::creating(static function () use (&$fail) {
            if ($fail) {
                throw new \RuntimeException('Synthetic response write failure');
            }
        });
        try {
            app(Consultations::class)->vote($this->learner, $row->id, $this->answers($row));
            $this->fail('The write failure was ignored.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Synthetic response write failure', $error->getMessage());
        } finally {
            $fail = false;
        }
        $this->assertSame(0, Participation::count());
        $this->assertSame(0, Response::count());
        $this->assertFalse(app(Consultations::class)->ballot($this->learner, $row->id)['participated']);
        app(Consultations::class)->vote($this->learner, $row->id, $this->answers($row));
        $this->assertSame(1, Participation::count());
        $this->assertSame(1, Response::count());
    }

    public function test_physical_school_foreign_key_refuses_a_cross_school_participation(): void
    {
        $row = $this->create();
        try {
            DB::table('consultation_participation')->insert(['school_id' => 2, 'consultation_id' => $row->id, 'user_id' => $this->schoolB->ids['asha_user']]);
            $this->fail('Cross-school ballot accepted');
        } catch (QueryException) {
            $this->assertSame(0, Participation::count());
        }
    }

    public function test_expired_scheduled_consultation_closes_without_late_opening_and_cancelled_ballots_stay_closed(): void
    {
        $row = $this->create(['opens_at' => '2026-02-10T09:00:00Z']);
        $cancelled = $this->create();
        app(Consultations::class)->end($this->superAdmin(), $cancelled->id, 'cancel', $this->op(['revision' => $cancelled->revision, 'reason' => 'Synthetic withdrawal']));
        $this->refused(404, fn () => app(Consultations::class)->vote($this->learner, $cancelled->id, $this->answers($cancelled)));
        $this->travelTo(CarbonImmutable::parse('2026-02-11T09:00:00Z'));
        $this->assertSame(1, app(Consultations::class)->advance());
        $this->assertSame('closed', $row->fresh()->status);
        $this->assertSame(1, OutboxEvent::where('type', 'consultations.opened')->count()); // Only the original immediate opening.
        $this->refused(409, fn () => app(Consultations::class)->publish($this->superAdmin(), $row->id, $this->op(['revision' => $row->fresh()->revision])));
    }

    public function test_facilitator_school_cohorts_are_denied_but_actual_parent_and_own_group_scopes_remain(): void
    {
        $actor = $this->parent;
        $actor->assignRole('Facilitator');
        SchoolMembership::updateOrCreate(['school_id' => 1, 'user_id' => $actor->id, 'role' => 'Facilitator'], ['status' => 'active', 'starts_on' => '2026-01-01']);
        $school = $this->create();
        $parents = $this->create(['audience' => ['kind' => 'school', 'parents' => true]]);
        $this->refused(404, fn () => app(Consultations::class)->ballot($actor, $school->id));
        $this->assertSame($parents->id, app(Consultations::class)->ballot($actor, $parents->id)['id']);
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
        $group = ActivityGroup::create(['session_year_id' => $this->year, 'name' => 'Synthetic coached group', 'discipline' => 'Sport', 'season' => '2026',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'gender' => 'any', 'active' => true]);
        $grant = ActivityStaff::create(['activity_group_id' => $group->id, 'user_id' => $actor->id, 'role' => 'facilitator', 'cascade' => false,
            'active' => true, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic own group only']);
        $own = $this->create(['audience' => ['kind' => 'activity_group', 'target_id' => (int) $group->id, 'parents' => false, 'include_staff' => true]]);
        $this->assertSame($own->id, app(Consultations::class)->ballot($actor, $own->id)['id']);
        $grant->update(['active' => false]);
        $this->refused(404, fn () => app(Consultations::class)->ballot($actor, $own->id));
        $this->refused(403, fn () => app(Consultations::class)->configuration($actor, $own->id));
    }
}
