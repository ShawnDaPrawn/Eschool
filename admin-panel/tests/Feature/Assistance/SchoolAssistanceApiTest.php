<?php

declare(strict_types=1);

namespace Tests\Feature\Assistance;

use App\Models\Ai\AiSchoolLimit;
use App\Models\Assistance\AssistanceTurn;
use App\Models\Assistance\SupportMessage;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolModule;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class SchoolAssistanceApiTest extends TestCase
{
    use UsesPracticeScenario, ValidatesOpenApiResponses;

    private string $base = '/api/v2/school-assistance';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'ask_school'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $this->actor('farid')->forceFill(['password_change_required' => false])->save();
        $this->superAdmin()->forceFill(['password_change_required' => false])->save();
        Http::preventStrayRequests();
    }

    private function family(): string
    {
        return $this->base.'/family/learners/'.$this->schoolA->ids['asha'];
    }

    private function publish(): string
    {
        return $this->actingAs($this->superAdmin())->postJson($this->base.'/office/documents', ['operation_id' => (string) Str::uuid(),
            'expected_revision' => 0, 'title' => 'School hours', 'markdown' => 'Lessons start at 08:00.',
            'source_reference' => 'Approved school handbook', 'identifiers_reviewed' => true])->assertOk()->json('data.id');
    }

    public function test_parent_reads_approved_documents_with_exact_account_and_school_binding(): void
    {
        $id = $this->publish();
        $this->actingAs($this->actor('farid'))->getJson($this->base.'/configuration')->assertOk()->assertJsonPath('data.can_parent', true)
            ->assertJsonPath('data.can_office', false)->assertJsonPath('data.answer_available', false);
        $this->getJson($this->family().'/documents')->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.school_id', 1)
            ->assertJsonPath('data.reader_id', $this->actor('farid')->id)->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson($this->family().'/documents/'.$id)->assertOk()->assertJsonPath('data.markdown', "Lessons start at 08:00\\.\n");
        $this->getJson($this->family().'/documents/'.$id, ['X-Account-Id' => (string) $this->actor('mona')->id])->assertForbidden();
        $this->actingAs($this->actor('asha'))->getJson($this->family().'/documents/'.$id)->assertForbidden();
        $this->actingAs($this->actor('farid'))->getJson($this->base.'/family/learners/'.$this->schoolB->ids['asha'].'/documents/'.$id)->assertNotFound();
    }

    public function test_real_parent_office_claim_reply_and_close_are_private_exact_operations(): void
    {
        $input = ['operation_id' => (string) Str::uuid(), 'text' => 'PRIVATE-PARENT school-hours question', 'language' => 'en'];
        $opened = $this->actingAs($this->actor('farid'))->postJson($this->family().'/threads', $input)->assertOk();
        $this->postJson($this->family().'/threads', $input)->assertOk()->assertExactJson($opened->json());
        $id = $opened->json('data.id');
        $this->actingAs($this->superAdmin())->getJson($this->base.'/office/queue')->assertOk()->assertJsonMissingPath('data.items.0.text');
        $this->getJson($this->base.'/threads/'.$id.'?persona=office')->assertNotFound();
        $claim = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1];
        $this->postJson($this->base.'/office/threads/'.$id.'/claim', $claim)->assertOk()->assertJsonPath('data.revision', 2);
        $this->getJson($this->base.'/threads/'.$id.'?persona=office')->assertOk()->assertJsonPath('data.messages.0.text', $input['text']);
        $this->postJson($this->base.'/threads/'.$id.'/reply', ['operation_id' => (string) Str::uuid(), 'persona' => 'office', 'text' => 'PRIVATE-OFFICE reply', 'language' => 'nso'])
            ->assertOk()->assertJsonPath('data.revision', 3);
        $this->actingAs($this->actor('farid'))->getJson($this->base.'/threads/'.$id.'?persona=parent')->assertOk()->assertJsonCount(2, 'data.messages');
        $this->postJson($this->base.'/threads/'.$id.'/close', ['operation_id' => (string) Str::uuid(), 'expected_revision' => 3, 'persona' => 'parent'])
            ->assertOk()->assertJsonPath('data.state', 'closed');
        $this->postJson($this->base.'/threads/'.$id.'/reply', $input + ['persona' => 'parent'])->assertConflict();
        $this->assertSame(2, SupportMessage::count());
    }

    public function test_review_turn_and_disabled_inference_contract_preserve_handoff_independence(): void
    {
        $id = $this->publish();
        $input = ['operation_id' => (string) Str::uuid(), 'documents' => [$id], 'include_calendar' => false,
            'include_records' => false, 'language' => 'af', 'question' => 'When do lessons start?'];
        $review = $this->actingAs($this->actor('farid'))->postJson($this->family().'/answers', $input)->assertOk()->assertJsonPath('data.state', 'prepared');
        $turn = $review->json('data.id');
        $this->getJson($this->base.'/turns/'.$turn)->assertOk()->assertJsonPath('data.citations.0.kind', 'document');
        $this->postJson($this->base.'/turns/'.$turn.'/run', ['identifiers_reviewed' => false])->assertUnprocessable();
        $this->postJson($this->base.'/turns/'.$turn.'/run', ['identifiers_reviewed' => true])->assertStatus(503)->assertJsonPath('data.reason', 'inference_disabled');
        $this->assertSame(1, AssistanceTurn::count());
        $this->actingAs($this->actor('mona'))->getJson($this->base.'/turns/'.$turn)->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_unauthenticated_refusals_private_query_and_fresh_guardian_withdrawal_have_no_body_leak(): void
    {
        $this->getJson($this->base.'/configuration')->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private');
        $input = ['operation_id' => (string) Str::uuid(), 'text' => 'PRIVATE-FORM-TEXT', 'language' => 'en'];
        $this->actingAs($this->actor('farid'))->postJson($this->family().'/threads?text=URL-SECRET', $input)->assertUnprocessable()->assertDontSee('URL-SECRET');
        $this->assertFalse(session()->has('_old_input'));
        $id = $this->postJson($this->family().'/threads', $input)->assertOk()->json('data.id');
        GuardianLink::where('guardian_user_id', $this->actor('farid')->id)->delete();
        $this->getJson($this->base.'/threads/'.$id.'?persona=parent')->assertNotFound()->assertDontSee('PRIVATE-FORM-TEXT');
        $this->postJson($this->family().'/threads', $input)->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_effective_school_cap_has_a_typed_private_429_without_provider_dispatch(): void
    {
        $doc = $this->publish();
        $turn = $this->actingAs($this->actor('farid'))->postJson($this->family().'/answers', [
            'operation_id' => (string) Str::uuid(), 'documents' => [$doc], 'include_calendar' => false,
            'include_records' => false, 'language' => 'en', 'question' => 'When do lessons start?',
        ])->assertOk()->json('data.id');
        config(['ai.inference.enabled' => true, 'ai.inference.features.school_assistant.enabled' => true,
            'ai.inference.features.school_assistant.model' => 'bounded-protocol-test',
            'ai.inference.features.school_assistant.cents_per_million_tokens' => 1000]);
        AiSchoolLimit::create(['limits' => ['school_daily_tokens' => 0]]);
        $this->postJson($this->base.'/turns/'.$turn.'/run', ['identifiers_reviewed' => true])->assertStatus(429)
            ->assertJsonPath('data.reason', 'school_daily_cap')->assertHeader('Cache-Control', 'no-store, private');
        Http::assertNothingSent();
    }

    public function test_readonly_module_preserves_parent_sources_and_threads_without_write_controls(): void
    {
        $doc = $this->publish();
        $thread = $this->actingAs($this->actor('farid'))->postJson($this->family().'/threads', [
            'operation_id' => (string) Str::uuid(), 'text' => 'Existing parent request', 'language' => 'en',
        ])->assertOk()->json('data.id');
        SchoolModule::where('module', 'ask_school')->update(['read_only' => true]);
        $this->getJson($this->base.'/configuration')->assertOk()->assertJsonPath('data.writable', false);
        $this->getJson($this->family().'/documents/'.$doc)->assertOk();
        $this->getJson($this->base.'/threads/'.$thread.'?persona=parent')->assertOk();
        $this->postJson($this->base.'/threads/'.$thread.'/reply', ['operation_id' => (string) Str::uuid(),
            'text' => 'Must not be written', 'language' => 'en', 'persona' => 'parent'])->assertForbidden();
        $this->assertSame(1, SupportMessage::count());
    }
}
