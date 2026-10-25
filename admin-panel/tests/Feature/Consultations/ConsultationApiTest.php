<?php

declare(strict_types=1);

namespace Tests\Feature\Consultations;

use App\Models\Consultations\Consultation;
use App\Models\Consultations\Participation;
use App\Models\Consultations\Response;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\ParentPolicyService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class ConsultationApiTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $learner;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        Queue::fake();
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        app(ModuleGate::class)->change('consultations', true, $this->superAdmin());
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->schoolA->ids['session_year'],
            'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic API consultation scope']);
        $this->learner = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        ParentPolicyService::acceptCurrent($this->learner);
        ParentPolicyService::acceptCurrent($this->parent);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'relationship' => 'father']);
    }

    private function api(User $actor): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken('consultation-api-test')->plainTextToken)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id]);
    }

    private function data(array $extra = []): array
    {
        return $extra + ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->schoolA->ids['session_year'],
            'type' => 'survey', 'title' => 'Synthetic family consultation', 'instructions' => 'Choose once.', 'anonymous' => true, 'results_visibility' => 'after_close',
            'opens_at' => now()->subHour()->toISOString(), 'closes_at' => now()->addDay()->toISOString(), 'audience' => ['kind' => 'school', 'parents' => true],
            'questions' => [['kind' => 'single', 'label' => 'Synthetic choice', 'required' => true, 'options' => [['label' => 'Morning'], ['label' => 'Afternoon']]]]];
    }

    private function create(array $extra = []): int
    {
        $response = $this->api($this->superAdmin())->postJson('/api/v2/consultations', $this->data($extra))->assertOk()->assertHeader('Cache-Control', 'no-store, private');

        return (int) $response->json('data.id');
    }

    private function publish(int $id): void
    {
        $revision = Consultation::findOrFail($id)->revision;
        $response = $this->api($this->superAdmin())->postJson("/api/v2/consultations/$id/publish", ['operation_id' => (string) Str::uuid(), 'revision' => $revision])->assertOk();
    }

    public static function operations(): iterable
    {
        foreach (json_decode(file_get_contents(dirname(__DIR__, 4).'/engineering/contracts/consultation-operations.json'), true, 512, JSON_THROW_ON_ERROR) as $op) {
            yield $op['operation_id'] => [$op['method'], preg_replace('/{[^}]+}/', '1', $op['path'])];
        }
    }

    #[DataProvider('operations')]
    public function test_every_operation_requires_authentication_and_private_error_headers(string $method, string $path): void
    {
        $response = $this->json($method, '/api/v2'.$path)->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_existing_private_wrapper_subclasses_keep_refusal_headers_before_authentication(): void
    {
        foreach ([['GET', '/wellbeing/access'], ['POST', '/wellbeing/notices/1/read'], ['GET', '/growth/mastery/configuration'], ['POST', '/growth/mastery/groups/1/rebuild']] as [$method, $path]) {
            $this->json($method, '/api/v2'.$path)->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private')
                ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    public function test_full_typed_configuration_ballot_vote_results_history_and_response_workflow(): void
    {
        $id = $this->create();
        $this->publish($id);
        foreach (['/access', '', '/managed', '/audiences?kind=homeroom', "/$id/configuration", "/$id/history"] as $suffix) {
            $response = $this->api($this->superAdmin())->getJson('/api/v2/consultations'.$suffix)->assertOk();
        }
        $history = $this->api($this->superAdmin())->getJson("/api/v2/consultations/$id/history")->assertOk();
        $event = $history->json('data.items.0.id');
        $detail = $this->api($this->superAdmin())->getJson("/api/v2/consultations/$id/history/$event")->assertOk();
        $ballot = $this->api($this->parent)->getJson("/api/v2/consultations/$id")->assertOk()->assertJsonPath('data.participated', false);
        $question = $ballot->json('data.questions.0');
        $answer = ['answers' => [['question_id' => $question['id'], 'option_ids' => [$question['options'][0]['id']]]]];
        $posted = $this->api($this->parent)->postJson("/api/v2/consultations/$id/vote", $answer)->assertOk()->assertJsonPath('data.participated', true);
        $this->api($this->parent)->postJson("/api/v2/consultations/$id/vote", $answer)->assertConflict();
        $this->api($this->parent)->getJson("/api/v2/consultations/$id/results")->assertForbidden();
        $this->api($this->superAdmin())->postJson("/api/v2/consultations/$id/close", ['operation_id' => (string) Str::uuid(), 'revision' => Consultation::findOrFail($id)->revision, 'reason' => 'Synthetic close'])->assertOk();
        foreach ([[$this->parent, "/$id/results"], [$this->superAdmin(), "/$id/managed-results"], [$this->superAdmin(), "/$id/responses"]] as [$actor, $path]) {
            $response = $this->api($actor)->getJson('/api/v2/consultations'.$path)->assertOk();
        }
        $this->assertNull(Response::sole()->respondent_user_id);
        $this->assertSame(1, Participation::count());
    }

    public function test_wrong_role_account_school_and_unpublished_source_are_refused_before_validation(): void
    {
        $id = $this->create();
        $this->api($this->parent)->postJson('/api/v2/consultations', [])->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
        $this->api($this->parent)->getJson("/api/v2/consultations/$id")->assertNotFound();
        $this->api($this->superAdmin())->withHeaders(['X-Account-Id' => (string) $this->parent->id])->getJson('/api/v2/consultations/managed')->assertForbidden();
        $this->api($this->superAdmin())->withHeaders(['X-School-Id' => '2'])->getJson('/api/v2/consultations/managed')->assertForbidden();
        $this->publish($id);
        $this->api($this->learner)->getJson("/api/v2/consultations/$id")->assertNotFound();
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['status' => 'ended']);
        $this->api($this->parent)->postJson("/api/v2/consultations/$id/vote", [])->assertNotFound();
        $this->api($this->parent)->getJson('/api/v2/consultations')->assertOk()->assertJsonPath('data.items', [])->assertJsonPath('data.last_page', 1);
    }

    public function test_a_legitimate_other_school_manager_cannot_read_or_mutate_a_bound_consultation(): void
    {
        $id = $this->create();
        $other = app(CurrentSchool::class)->run(2, function () {
            $actor = User::findOrFail($this->schoolB->ids['tara_user']);
            SchoolMembership::create(['school_id' => 2, 'user_id' => $actor->id, 'role' => SchoolRoles::SUPER_ADMIN, 'status' => 'active', 'starts_on' => '2026-01-01']);
            $actor->assignRole(SchoolRoles::SUPER_ADMIN);
            app(ModuleGate::class)->change('consultations', true, $actor);

            return $actor;
        });
        foreach ([['GET', "/$id/configuration"], ['GET', "/$id/history"], ['POST', "/$id"], ['POST', "/$id/publish"], ['POST', "/$id/close"], ['POST', "/$id/cancel"]] as [$method, $suffix]) {
            $this->api($other)->withHeaders(['X-School-Id' => '2'])->json($method, '/api/v2/consultations'.$suffix, [])
                ->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        }
        $this->assertSame('draft', Consultation::findOrFail($id)->status);
    }

    public function test_private_vote_rejects_query_and_actor_fields_and_readonly_retains_ballot(): void
    {
        $id = $this->create();
        $this->publish($id);
        $ballot = $this->api($this->parent)->getJson("/api/v2/consultations/$id")->assertOk();
        $q = $ballot->json('data.questions.0');
        $answer = ['answers' => [['question_id' => $q['id'], 'option_ids' => [$q['options'][0]['id']]]]];
        $this->api($this->parent)->postJson("/api/v2/consultations/$id/vote?user_id=1", $answer)->assertUnprocessable();
        $this->api($this->parent)->postJson("/api/v2/consultations/$id/vote", ['user_id' => 1] + $answer)->assertUnprocessable();
        $this->assertSame(0, Participation::count());
        SchoolModule::where('module', 'consultations')->update(['read_only' => true]);
        $this->api($this->parent)->getJson("/api/v2/consultations/$id")->assertOk()->assertJsonPath('data.writable', false);
        $this->api($this->parent)->postJson("/api/v2/consultations/$id/vote", $answer)->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_saved_historical_year_cannot_be_changed_by_a_current_year_body(): void
    {
        $id = $this->create();
        $old = SessionYear::findOrFail($this->schoolA->ids['session_year'])->replicate();
        $old->name = 'Synthetic historical year';
        $old->default = 0;
        $old->save();
        DB::table('consultations')->where('id', $id)->update(['session_year_id' => $old->id]);
        $this->api($this->superAdmin())->postJson("/api/v2/consultations/$id", $this->data(['revision' => 1]))->assertConflict();
        $this->assertSame((int) $old->id, (int) Consultation::findOrFail($id)->session_year_id);
    }
}
