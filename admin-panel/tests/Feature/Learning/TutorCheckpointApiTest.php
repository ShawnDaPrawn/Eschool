<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Growth\MasteryEvidence;
use App\Models\Learning\TutorCheckpoint;
use App\Models\Platform\SchoolModule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class TutorCheckpointApiTest extends TestCase
{
    use UsesPracticeScenario, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'tutor'], ['enabled' => true, 'in_package' => true]);
        Http::preventStrayRequests();
        Http::fake();
    }

    private function signIn(string $name = 'asha'): void
    {
        $this->app['auth']->forgetGuards();
        $actor = $this->actor($name);
        $this->actingAs($actor, 'sanctum')->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id]);
    }

    private function start(): array
    {
        $this->approve();
        $this->signIn();
        $input = ['operation_id' => (string) Str::uuid(), 'group_id' => $this->group, 'source_version_ids' => [$this->source], 'topic_id' => $this->nodes[$this->keys[1]]];
        $session = $this->postJson('/api/v2/tutor/sessions', $input)->assertOk()->json('data');
        $this->postJson('/api/v2/tutor/sessions', $input)->assertOk()->assertJsonPath('data.id', $session['id']);
        $operation = (string) Str::uuid();
        $path = '/api/v2/tutor/sessions/'.$session['id'].'/checkpoints';
        $checkpoint = $this->postJson($path, ['operation_id' => $operation])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');

        return [$session, $checkpoint, $path];
    }

    public function test_topic_session_checkpoint_and_verified_answer_are_typed_private_and_independent_of_external_inference(): void
    {
        [$session, $checkpoint, $path] = $this->start();
        $this->getJson('/api/v2/tutor/configuration')->assertOk()->assertJsonFragment(['id' => $this->nodes[$this->keys[1]], 'name' => 'Fractions'])->assertJsonPath('data.checkpoints_enabled', true);
        $this->postJson($path, ['operation_id' => $checkpoint['operation_id']])->assertOk()->assertJsonPath('data', $checkpoint);
        $this->getJson($path.'/'.$checkpoint['id'])->assertOk()->assertJsonPath('data.correct', null)->assertJsonPath('data.explanation', null)->assertJsonMissingPath('data.question.answers');
        $this->getJson('/api/v2/tutor/sessions/'.$session['id'])->assertOk()->assertJsonPath('data.topic_id', $session['topic_id'])->assertJsonPath('data.checkpoints.0.operation_id', $checkpoint['operation_id']);
        $answer = ['operation_id' => (string) Str::uuid(), 'answer' => '0.500'];
        $result = $this->postJson($path.'/'.$checkpoint['id'].'/answer', $answer)->assertOk()->assertJsonPath('data.correct', true)->assertJsonPath('data.verified_by', 'approved_answer_definition')->json('data');
        $this->postJson($path.'/'.$checkpoint['id'].'/answer', $answer)->assertOk()->assertJsonPath('data', $result);
        $this->assertSame(1, MasteryEvidence::where('source_type', 'tutor_checkpoint')->count());
        Http::assertNothingSent();
    }

    public function test_private_queries_unknown_fields_and_changed_answer_replay_are_refused_without_flash_or_duplicate_evidence(): void
    {
        [, $checkpoint, $path] = $this->start();
        $url = $path.'/'.$checkpoint['id'].'/answer';
        $input = ['operation_id' => (string) Str::uuid(), 'answer' => '0.25'];
        $this->postJson($url.'?answer=PRIVATE-QUERY-CANARY', $input)->assertUnprocessable()->assertHeader('Referrer-Policy', 'no-referrer');
        $this->postJson($url, $input + ['correct' => true])->assertUnprocessable();
        $this->postJson($url, array_replace($input, ['answer' => 0.25]))->assertUnprocessable();
        $this->postJson($url, array_replace($input, ['answer' => 'PRIVATE-INVALID-ANSWER-CANARY']))->assertUnprocessable();
        $this->assertNull(TutorCheckpoint::sole()->answered_at);
        $this->postJson($url, $input)->assertOk()->assertJsonPath('data.correct', false);
        $this->postJson($url, array_replace($input, ['answer' => '0.5']))->assertConflict();
        $this->assertSame(0, MasteryEvidence::where('source_type', 'tutor_checkpoint')->sole()->achieved_units);
        $this->assertSame([], session()->getOldInput());
        Http::assertNothingSent();
    }

    public function test_parent_teacher_changed_account_and_other_school_are_rejected_before_question_or_explanation(): void
    {
        [, $checkpoint, $path] = $this->start();
        $url = $path.'/'.$checkpoint['id'];
        $this->withHeader('X-Account-Id', (string) $this->actor('farid')->id)->getJson($url)->assertForbidden()->assertDontSee('One half as a decimal?');
        $this->signIn();
        $this->withHeader('X-School-Id', '2')->getJson($url)->assertForbidden()->assertDontSee('One half as a decimal?');
        foreach (['farid', 'tara'] as $name) {
            $this->signIn($name);
            $this->getJson($url)->assertNotFound()->assertJsonMissingPath('data.question');
            $this->postJson($url.'/answer', ['operation_id' => (string) Str::uuid(), 'answer' => '0.5'])->assertNotFound();
        }
        $this->assertSame(0, MasteryEvidence::where('source_type', 'tutor_checkpoint')->count());
        Http::assertNothingSent();
    }

    public function test_practice_withdrawal_disables_checkpoint_writes_but_keeps_subject_session_reads(): void
    {
        [$session, $checkpoint, $path] = $this->start();
        SchoolModule::where('module', 'daily_practice')->update(['enabled' => false]);
        $this->getJson('/api/v2/tutor/configuration')->assertOk()->assertJsonPath('data.checkpoints_enabled', false);
        $this->getJson('/api/v2/tutor/sessions/'.$session['id'])->assertOk()->assertJsonPath('data.checkpoints', []);
        $this->getJson($path.'/'.$checkpoint['id'])->assertForbidden()->assertJsonMissingPath('data.question');
        $this->postJson($path.'/'.$checkpoint['id'].'/answer', ['operation_id' => (string) Str::uuid(), 'answer' => '0.5'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_all_checkpoint_routes_reach_authentication_with_private_headers(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $id = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
        $base = '/api/v2/tutor/sessions/'.$id.'/checkpoints';
        foreach ([['POST', $base], ['GET', $base.'/'.$id], ['POST', $base.'/'.$id.'/answer']] as [$method, $uri]) {
            $this->json($method, $uri)->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }
}
