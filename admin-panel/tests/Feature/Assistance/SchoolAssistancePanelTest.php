<?php

declare(strict_types=1);

namespace Tests\Feature\Assistance;

use App\Models\Assistance\KnowledgeDocument;
use App\Models\Assistance\SupportMessage;
use App\Models\Assistance\SupportThread;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Services\Assistance\AssistanceInference;
use App\Services\Assistance\KnowledgeDocuments;
use App\Services\Assistance\SchoolSupportThreads;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

class SchoolAssistancePanelTest extends TestCase
{
    use UsesPracticeScenario;

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

    private function fields(array $input = []): array
    {
        return array_replace(['expected_account_id' => $this->superAdmin()->id, 'expected_school_id' => 1, 'operation_id' => (string) Str::uuid()], $input);
    }

    private function thread(): string
    {
        return app(SchoolSupportThreads::class)->open($this->actor('farid'), $this->schoolA->ids['asha'], [
            'operation_id' => (string) Str::uuid(), 'text' => 'PRIVATE-PARENT office request', 'language' => 'en',
        ])['id'];
    }

    public function test_office_claim_before_body_reply_exact_retry_close_and_original_translation_review(): void
    {
        $id = $this->thread();
        $this->actingAs($this->superAdmin())->get('/ask-school')->assertOk()->assertSee('Claim request')
            ->assertDontSee('PRIVATE-PARENT')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/ask-school/threads/'.$id)->assertNotFound()->assertDontSee('PRIVATE-PARENT');
        $this->post('/ask-school/threads/'.$id.'/claim', $this->fields(['expected_revision' => 1]))->assertRedirect();
        $this->get('/ask-school/threads/'.$id)->assertOk()->assertSee('PRIVATE-PARENT office request');
        $reply = $this->fields(['text' => 'PRIVATE-OFFICE original reply', 'language' => 'en']);
        $this->post('/ask-school/threads/'.$id.'/reply', $reply)->assertRedirect();
        $this->post('/ask-school/threads/'.$id.'/reply', $reply)->assertRedirect();
        $this->assertSame(2, SupportMessage::count());
        $message = SupportMessage::where('author_kind', 'parent')->sole();
        $response = $this->post('/ask-school/threads/'.$id.'/translate', $this->fields(['message_id' => $message->id, 'language' => 'af']))->assertRedirect();
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('PRIVATE-PARENT office request')
            ->assertSee('Translation is currently unavailable')->assertDontSee('&quot;task&quot;');
        $this->post('/ask-school/threads/'.$id.'/close', $this->fields(['expected_revision' => 3]))->assertRedirect();
        $this->assertSame('closed', SupportThread::findOrFail($id)->state);
        Http::assertNothingSent();
    }

    public function test_approved_document_new_version_withdrawal_and_readonly_panel(): void
    {
        $input = $this->fields(['expected_revision' => 0, 'title' => 'School hours', 'markdown' => 'Approved private school source',
            'source_reference' => 'School handbook', 'identifiers_reviewed' => '1']);
        $this->actingAs($this->superAdmin())->post('/ask-school/documents', $input)->assertRedirect();
        $document = KnowledgeDocument::sole();
        $this->get('/ask-school?document='.$document->id)->assertOk()->assertSee('Approved private school source');
        $revision = $this->fields(['document_key' => $document->document_key, 'expected_revision' => 1, 'title' => 'Updated school hours',
            'markdown' => 'PRIVATE changed approved source', 'source_reference' => '', 'identifiers_reviewed' => '1']);
        $this->post('/ask-school/documents', $revision)->assertUnprocessable()->assertSee('name="document_key" value="'.$document->document_key.'"', false)
            ->assertSee('PRIVATE changed approved source')->assertSessionMissing('_old_input');
        $revision['source_reference'] = 'Updated handbook';
        $this->post('/ask-school/documents', $revision)->assertRedirect();
        $current = KnowledgeDocument::where('document_key', $document->document_key)->latest('revision')->firstOrFail();
        $this->assertSame(2, $current->revision);
        $this->assertSame('withdrawn', $document->fresh()->state);
        $this->post('/ask-school/documents/'.$current->id.'/withdraw', $this->fields())->assertRedirect();
        $this->assertSame('withdrawn', $document->fresh()->state);
        SchoolModule::where('module', 'ask_school')->update(['read_only' => true]);
        $this->get('/ask-school?document='.$document->id)->assertOk()->assertSee('Approved private school source')->assertDontSee('id="assistance-doc-title"', false);
        $this->post('/ask-school/documents', $input)->assertForbidden();
    }

    public function test_validation_and_historical_header_return_private_draft_without_session_flash(): void
    {
        $input = $this->fields(['expected_revision' => 0, 'title' => 'Private title', 'markdown' => 'PRIVATE-UNSAVED document', 'source_reference' => '', 'identifiers_reviewed' => '1']);
        $this->actingAs($this->superAdmin())->post('/ask-school/documents', $input)->assertUnprocessable()
            ->assertSee('PRIVATE-UNSAVED document')->assertSessionMissing('_old_input')->assertHeader('Cache-Control', 'no-store, private');
        $past = SessionYear::create(['name' => 'Historical header', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $this->withSession(['session_year' => $past->id])->post('/ask-school/documents', $input)->assertStatus(423)
            ->assertSee('PRIVATE-UNSAVED document')->assertSessionMissing('_old_input');
        $this->assertSame(0, KnowledgeDocument::count());
    }

    public function test_wrong_role_school_owner_and_replaced_cookie_account_refuse_before_private_input(): void
    {
        $id = $this->thread();
        $this->actingAs($this->actor('farid'))->get('/ask-school')->assertForbidden()->assertDontSee('PRIVATE-PARENT');
        $this->actingAs($this->superAdmin())->get('/ask-school/threads/'.$id)->assertNotFound();
        $this->post('/ask-school/threads/'.$id.'/claim', $this->fields(['expected_revision' => 1]))->assertRedirect();
        $this->actingAs($this->actor('tara'))->get('/ask-school/threads/'.$id)->assertForbidden()->assertDontSee('PRIVATE-PARENT');
        $this->actingAs($this->superAdmin())->post('/ask-school/threads/'.$id.'/reply', $this->fields(['expected_account_id' => $this->actor('tara')->id,
            'text' => 'PRIVATE-STALE draft', 'language' => 'en']))->assertForbidden()->assertDontSee('PRIVATE-STALE');
        $this->post('/ask-school/threads/'.$id.'/reply', $this->fields(['expected_school_id' => 2,
            'text' => 'PRIVATE-OTHER-SCHOOL draft', 'language' => 'en']))->assertForbidden()->assertDontSee('PRIVATE-OTHER-SCHOOL');
        GuardianLink::where('guardian_user_id', $this->actor('farid')->id)->delete();
        $this->get('/ask-school/threads/'.$id)->assertNotFound()->assertDontSee('PRIVATE-PARENT');
        $this->post('/ask-school/threads/'.$id.'/reply', $this->fields(['text' => 'PRIVATE-STALE draft', 'language' => 'en']))->assertNotFound()->assertDontSee('PRIVATE-STALE');
        $this->assertSame(1, SupportMessage::count());
    }

    public function test_foreign_school_document_id_cannot_be_loaded_by_a_valid_selected_school_office(): void
    {
        $id = app(CurrentSchool::class)->run(2, fn () => KnowledgeDocument::create([
            'id' => (string) Str::uuid(), 'document_key' => (string) Str::uuid(), 'revision' => 1,
            'approved_by' => $this->schoolB->ids['tara_user'], 'title' => 'PRIVATE-OTHER-SCHOOL source', 'markdown' => 'PRIVATE-OTHER-SCHOOL body',
            'source_reference' => 'Foreign handbook', 'state' => 'published', 'identifiers_reviewed' => true, 'content_hash' => str_repeat('a', 64), 'published_at' => now()->utc(),
        ])->id);
        $this->actingAs($this->superAdmin())->get('/ask-school?document='.$id)->assertNotFound()->assertDontSee('PRIVATE-OTHER-SCHOOL');
    }

    public function test_private_query_and_unowned_turns_never_reach_panel_or_old_input(): void
    {
        $doc = app(KnowledgeDocuments::class)->publish($this->superAdmin(), ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0,
            'title' => 'Source', 'markdown' => 'School hours', 'source_reference' => 'Handbook', 'identifiers_reviewed' => true]);
        $turn = app(AssistanceInference::class)->prepare($this->actor('farid'), $this->schoolA->ids['asha'], 'answer', [
            'operation_id' => (string) Str::uuid(), 'documents' => [$doc['id']], 'include_records' => false, 'include_calendar' => false,
            'language' => 'en', 'question' => 'PRIVATE-QUESTION',
        ]);
        $this->actingAs($this->superAdmin())->get('/ask-school?turn='.$turn['id'])->assertNotFound()->assertDontSee('PRIVATE-QUESTION');
        $this->post('/ask-school/documents?markdown=PRIVATE-URL', $this->fields())->assertUnprocessable()->assertDontSee('PRIVATE-URL')->assertSessionMissing('_old_input');
    }
}
