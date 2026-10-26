<?php

declare(strict_types=1);

namespace Tests\Feature\Assistance;

use App\Models\Ai\AiUsage;
use App\Models\Assistance\AssistanceOperation;
use App\Models\Assistance\AssistanceTurn;
use App\Models\Assistance\KnowledgeDocument;
use App\Models\Assistance\SupportMessage;
use App\Models\Assistance\SupportThread;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\Role;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\Inference\InferenceAudience;
use App\Services\Ai\Inference\InferenceGateway;
use App\Services\Assistance\AssistanceInference;
use App\Services\Assistance\AssistantFeaturePolicy;
use App\Services\Assistance\KnowledgeDocuments;
use App\Services\Assistance\SchoolSupportThreads;
use App\Services\Assistance\TranslationFeaturePolicy;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

/** Synthetic bounded HTTP protocol tests, not real-provider qualification. */
class AssistanceInferenceTest extends TestCase
{
    use UsesPracticeScenario;

    private array $document;

    private int $calls = 0;

    private bool $badCitation = false;

    private mixed $duringInference = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'ask_school'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        foreach ([$this->actor('farid'), $this->superAdmin()] as $actor) {
            $actor->forceFill(['password_change_required' => false])->save();
        }
        $this->document = app(KnowledgeDocuments::class)->publish($this->superAdmin(), ['operation_id' => (string) Str::uuid(),
            'expected_revision' => 0, 'title' => 'School hours', 'markdown' => 'Lessons start at 08:00. Ask the office about exceptions.',
            'source_reference' => 'School handbook 2026', 'identifiers_reviewed' => true]);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            $this->calls++;
            if (str_ends_with($request->url(), '/moderations')) {
                return Http::response(['results' => [['flagged' => false]]]);
            }
            if ($this->duringInference !== null) {
                ($this->duringInference)();
            }
            $input = json_decode($request['messages'][1]['content'], true, 16, JSON_THROW_ON_ERROR);
            $output = ['language' => $input['language'], 'text' => 'Synthetic protocol answer from the approved source.',
                'citations' => [$this->badCitation ? 'invented-source' : 's1'], 'handoff' => false];

            return Http::response(['model' => 'assistance-protocol-test', 'choices' => [['finish_reason' => 'stop',
                'message' => ['role' => 'assistant', 'content' => json_encode($output)]]],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 40, 'total_tokens' => 160]]);
        });
    }

    private function input(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'documents' => [$this->document['id']], 'include_calendar' => false,
            'include_records' => false, 'language' => 'af', 'question' => 'PRIVATE-QUESTION: When do lessons start for Asha?'];
    }

    private function prepare(?array $input = null): array
    {
        return app(AssistanceInference::class)->prepare($this->actor('farid'), $this->schoolA->ids['asha'], 'answer', $input ?? $this->input());
    }

    private function qualifyProtocol(): void
    {
        config(['ai.inference.enabled' => true, 'ai.providers.openai.qualified' => true, 'ai.providers.openai.key' => 'synthetic-test-only']);
        foreach (['school_assistant', 'message_translation'] as $feature) {
            config(['ai.inference.features.'.$feature.'.enabled' => true, 'ai.inference.features.'.$feature.'.model' => 'assistance-protocol-test',
                'ai.inference.features.'.$feature.'.cents_per_million_tokens' => 1000]);
        }
    }

    private function refuses(callable $action, array $allowed = [403, 404, 409]): void
    {
        try {
            $action();
            $this->fail('Unauthorised or stale assistance operation was accepted.');
        } catch (ModelNotFoundException) {
            $this->assertContains(404, $allowed);
        } catch (HttpExceptionInterface $error) {
            $this->assertContains($error->getStatusCode(), $allowed);
        }
    }

    public function test_review_is_exact_encrypted_minimised_and_disabled_provider_makes_no_call(): void
    {
        $input = $this->input();
        $turn = $this->prepare($input);
        $this->assertSame($turn, $this->prepare($input));
        $this->assertStringNotContainsString('Asha', $turn['review_text']);
        $this->assertStringContainsString('[person]', $turn['review_text']);
        $this->assertSame('document', $turn['citations'][0]['kind']);
        $this->assertStringNotContainsString('PRIVATE-QUESTION', DB::table('school_assistance_turns')->value('approved_input'));
        // The operation receipt retains only the opaque turn id, not a second
        // private prompt copy that would escape turn expiry/purge.
        $receipt = AssistanceOperation::where('kind', 'turn.prepare')->sole()->response;
        $this->assertSame(['id' => $turn['id']], $receipt);
        try {
            app(AssistanceInference::class)->run($this->actor('farid'), $turn['id'], true);
            $this->fail('Inference should stay disabled.');
        } catch (AiUnavailable $error) {
            $this->assertSame('inference_disabled', $error->reason);
        }
        $this->assertSame(0, $this->calls);
    }

    public function test_human_review_then_bounded_protocol_answer_replays_without_a_second_dispatch(): void
    {
        $turn = $this->prepare();
        $service = app(AssistanceInference::class);
        $this->refuses(fn () => $service->run($this->actor('farid'), $turn['id'], false), [422]);
        $this->qualifyProtocol();
        $result = $service->run($this->actor('farid'), $turn['id'], true);
        $this->assertSame('completed', $result['state']);
        $this->assertSame(['s1'], $result['response']['citations']);
        $this->assertSame('af', $result['response']['language']);
        $this->assertSame($result, $service->run($this->actor('farid'), $turn['id'], true));
        $this->assertSame(3, $this->calls);
        $usage = AiUsage::where('operation_id', $turn['id'])->sole();
        $this->assertSame('school_assistant', $usage->feature);
        $this->assertSame(160, $usage->used_units);
        $this->assertStringNotContainsString('Synthetic protocol answer', DB::table('school_assistance_turns')->value('response'));
        $this->travel(16)->minutes();
        $this->assertSame(0, Artisan::call('assistance:prune-private'));
        $usage->refresh();
        $this->assertNull($usage->inference_output);
        $this->assertNull($usage->inference_sources);
        $this->assertSame(160, $usage->used_units);
    }

    public function test_other_owner_role_school_and_free_source_payload_cannot_retrieve_a_turn(): void
    {
        $turn = $this->prepare();
        $service = app(AssistanceInference::class);
        foreach (['mona', 'asha', 'tara'] as $name) {
            $this->refuses(fn () => $service->view($this->actor($name), $turn['id']));
        }
        $this->refuses(fn () => app(AssistantFeaturePolicy::class)->resolve($this->actor('farid'), Capability::Complete,
            new FeatureInferenceRequest('school_assistant', $turn['id'], ['turn_id' => $turn['id'], 'body' => 'Caller-provided source'])), [422]);
        $actor = $this->actor('farid');
        app(CurrentSchool::class)->set(2);
        $this->refuses(fn () => $service->view($actor, $turn['id']));
        $this->assertSame(0, $this->calls);
    }

    public function test_withdrawn_or_new_document_version_never_substitutes_a_prepared_source(): void
    {
        $turn = $this->prepare();
        KnowledgeDocument::findOrFail($this->document['id'])->update(['state' => 'withdrawn']);
        $this->refuses(fn () => app(AssistanceInference::class)->view($this->actor('farid'), $turn['id']), [404]);
        $this->assertSame(0, $this->calls);
    }

    public function test_two_way_translation_reads_exact_assigned_message_and_never_sends_it(): void
    {
        $threads = app(SchoolSupportThreads::class);
        $thread = $threads->open($this->actor('farid'), $this->schoolA->ids['asha'], ['operation_id' => (string) Str::uuid(), 'text' => 'Please clarify tomorrow’s school hours.', 'language' => 'en']);
        $threads->claim($this->superAdmin(), $thread['id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1]);
        $reply = $threads->reply($this->superAdmin(), $thread['id'], ['operation_id' => (string) Str::uuid(), 'text' => 'Lessons start at 08:00.', 'language' => 'en'], true);
        $service = app(AssistanceInference::class);
        $parentView = $service->prepare($this->actor('farid'), $this->schoolA->ids['asha'], 'translation',
            ['operation_id' => (string) Str::uuid(), 'message_id' => $reply['message_id'], 'language' => 'af']);
        $officeView = $service->prepare($this->superAdmin(), $this->schoolA->ids['asha'], 'translation',
            ['operation_id' => (string) Str::uuid(), 'message_id' => $thread['message_id'], 'language' => 'af'], true);
        $this->qualifyProtocol();
        $this->assertSame('completed', $service->run($this->actor('farid'), $parentView['id'], true)['state']);
        $this->assertSame('completed', $service->run($this->superAdmin(), $officeView['id'], true)['state']);
        $this->assertSame(2, SupportMessage::count());
        $this->assertSame('Please clarify tomorrow’s school hours.', SupportMessage::findOrFail($thread['message_id'])->text);
        GuardianLink::where('guardian_user_id', $this->actor('farid')->id)->delete();
        $this->refuses(fn () => $service->view($this->actor('farid'), $parentView['id']));
        $this->refuses(fn () => $service->view($this->superAdmin(), $officeView['id']));
    }

    public function test_invented_citation_is_quarantined_and_cannot_return_as_a_preview(): void
    {
        $turn = $this->prepare();
        $this->qualifyProtocol();
        $this->badCitation = true;
        try {
            app(AssistanceInference::class)->run($this->actor('farid'), $turn['id'], true);
            $this->fail('An unsupported citation must be refused.');
        } catch (AiUnavailable $error) {
            $this->assertSame('inference_quarantined', $error->reason);
        }
        $this->assertSame('quarantined', AiUsage::where('operation_id', $turn['id'])->sole()->state);
        $this->refuses(fn () => app(AssistanceInference::class)->view($this->actor('farid'), $turn['id']), [404]);
        $this->assertNull(AssistanceTurn::findOrFail($turn['id'])->response);
    }

    public function test_current_guardian_loss_during_external_protocol_call_prevents_output_linkage(): void
    {
        $turn = $this->prepare();
        $this->qualifyProtocol();
        $this->duringInference = fn () => GuardianLink::where('guardian_user_id', $this->actor('farid')->id)->delete();
        try {
            app(AssistanceInference::class)->run($this->actor('farid'), $turn['id'], true);
            $this->fail('Revoked guardian cannot receive a late answer.');
        } catch (AiUnavailable) {
            $this->assertNull(AssistanceTurn::findOrFail($turn['id'])->response);
        }
        $this->assertSame('failed', AiUsage::where('operation_id', $turn['id'])->sole()->state);
    }

    public function test_expired_turn_is_unreadable_and_private_fields_are_erased_even_with_module_off(): void
    {
        $turn = $this->prepare();
        $this->travel(16)->minutes();
        $this->refuses(fn () => app(AssistanceInference::class)->view($this->actor('farid'), $turn['id']), [404]);
        SchoolModule::where('module', 'ask_school')->update(['enabled' => false]);
        $this->assertSame(0, Artisan::call('assistance:prune-private'));
        $row = AssistanceTurn::findOrFail($turn['id']);
        foreach (['source_request', 'source_manifest', 'approved_input', 'citations', 'response'] as $field) {
            $this->assertNull($row->$field);
        }
        $this->assertNotNull($row->purged_at);
    }

    public function test_calendar_and_own_released_records_use_family_policy_without_private_case_queries(): void
    {
        $this->capture('4.00', '9.00');
        $input = $this->input();
        $input['documents'] = [];
        $input['include_records'] = true;
        $input['include_calendar'] = true;
        $before = $this->prepare($input);
        $this->assertNotContains('released_marks', array_column($before['citations'], 'kind'));
        $this->release();
        $input['operation_id'] = (string) Str::uuid();
        DB::enableQueryLog();
        $after = $this->prepare($input);
        $this->assertContains('released_marks', array_column($after['citations'], 'kind'));
        $this->assertContains('calendar', array_column($after['citations'], 'kind'));
        $this->assertContains('attendance', array_column($after['citations'], 'kind'));
        $sql = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        foreach (['support_cases', 'support_case_entries', 'incident_reports', 'learner_notes', 'risk_observations'] as $table) {
            $this->assertStringNotContainsString($table, $sql);
        }
        DB::disableQueryLog();
        $this->refuses(fn () => app(AssistanceInference::class)->view($this->actor('farid'), $before['id']), [409]);
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $role->givePermissionTo(['academic-structure-manage', 'communication-manage']);
        $admin = $this->actor('mona');
        $admin->syncRoles([$role]);
        $admin->forceFill(['password_change_required' => false, 'status' => 1, 'email_verified_at' => now()])->save();

        return $admin;
    }

    private function adminTurn(): array
    {
        $threads = app(SchoolSupportThreads::class);
        $thread = $threads->open($this->actor('farid'), $this->schoolA->ids['asha'], ['operation_id' => (string) Str::uuid(), 'text' => 'Please clarify school hours.', 'language' => 'en']);
        $admin = $this->admin();
        $this->refuses(fn () => app(AssistanceInference::class)->prepare($admin, $this->schoolA->ids['asha'], 'translation',
            ['operation_id' => (string) Str::uuid(), 'message_id' => $thread['message_id'], 'language' => 'af'], true), [404]);
        $threads->claim($admin, $thread['id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1]);
        $turn = app(AssistanceInference::class)->prepare($admin, $this->schoolA->ids['asha'], 'translation',
            ['operation_id' => (string) Str::uuid(), 'message_id' => $thread['message_id'], 'language' => 'af'], true);

        return [$admin, $turn];
    }

    public function test_literal_admin_can_translate_only_the_exact_claimed_office_source(): void
    {
        [$admin, $turn] = $this->adminTurn();
        $this->qualifyProtocol();
        $result = app(AssistanceInference::class)->run($admin, $turn['id'], true);
        $this->assertSame('completed', $result['state']);
        $this->assertSame(3, $this->calls);
        $this->assertSame(1, AiUsage::count());
        $this->refuses(fn () => app(AssistanceInference::class)->prepare($admin, $this->schoolA->ids['asha'], 'answer', $this->input()), [403]);
    }

    public function test_admin_source_and_office_withdrawal_and_missing_hook_cannot_create_usage(): void
    {
        [$admin, $turn] = $this->adminTurn();
        $this->qualifyProtocol();
        config(['ai.inference.staff_actor_policies' => []]);
        $this->refuses(fn () => app(AssistanceInference::class)->run($admin, $turn['id'], true), [403]);
        $this->assertSame(0, AiUsage::count());
        config(['ai.inference.staff_actor_policies.message_translation' => AssistantFeaturePolicy::class]);
        $this->refuses(fn () => app(AssistanceInference::class)->run($admin, $turn['id'], true), [403]);
        config(['ai.inference.staff_actor_policies.message_translation' => TranslationFeaturePolicy::class]);
        $roles = config('ai.inference.features.message_translation.roles');
        config(['ai.inference.features.message_translation.roles' => array_values(array_diff($roles, ['Admin']))]);
        $this->refuses(fn () => app(AssistanceInference::class)->run($admin, $turn['id'], true), [403]);
        config(['ai.inference.features.message_translation.roles' => $roles]);
        SupportThread::query()->update(['assigned_user_id' => $this->superAdmin()->id]);
        $this->refuses(fn () => app(AssistanceInference::class)->run($admin, $turn['id'], true), [404]);
        $this->assertSame(0, AiUsage::count());
        $this->assertSame(0, $this->calls);
    }

    public function test_admin_wrong_scope_unrelated_feature_and_withdrawn_permission_remain_denied(): void
    {
        [$admin, $turn] = $this->adminTurn();
        $this->qualifyProtocol();
        $row = AssistanceTurn::findOrFail($turn['id']);
        $row->update(['approved_at' => now()->utc()]);
        $scope = app(TranslationFeaturePolicy::class)->resolve($admin, Capability::Complete,
            new FeatureInferenceRequest('message_translation', $turn['id'], ['turn_id' => $turn['id']]));
        $configuration = new \ReflectionMethod(InferenceGateway::class, 'configuration');
        $gateway = app(InferenceGateway::class);
        foreach ([$this->actor('farid')->id, $admin->id] as $index => $id) {
            $bad = new AuthorizedInferenceScope($index === 0 ? 1 : 2, $id, $scope->inference,
                $scope->learnerId, audience: InferenceAudience::Staff);
            $this->refuses(fn () => $configuration->invoke($gateway, $admin, Capability::Complete, 'message_translation', $bad), [403]);
        }
        $parent = $this->actor('farid');
        $parentStaff = new AuthorizedInferenceScope(1, $parent->id, $scope->inference,
            $scope->learnerId, audience: InferenceAudience::Staff);
        $this->refuses(fn () => $configuration->invoke($gateway, $parent, Capability::Complete, 'message_translation', $parentStaff), [403]);
        config(['ai.inference.features.unrelated_probe' => config('ai.inference.features.message_translation')]);
        $this->refuses(fn () => $configuration->invoke($gateway, $admin, Capability::Complete, 'unrelated_probe', $scope), [403]);
        Role::where('name', 'Admin')->firstOrFail()->revokePermissionTo('communication-manage');
        $this->refuses(fn () => app(AssistanceInference::class)->run($admin, $turn['id'], true), [403]);
        $this->assertSame(0, AiUsage::count());
        $this->assertSame(0, $this->calls);
    }

    public function test_review_storage_bound_applies_even_when_external_inference_is_disabled(): void
    {
        $prepared = $this->prepare();
        $row = AssistanceTurn::findOrFail($prepared['id']);
        for ($i = 1; $i < 50; $i++) {
            $copy = $row->replicate();
            $copy->id = (string) Str::uuid();
            $copy->operation_id = (string) Str::uuid();
            $copy->save();
        }
        $this->refuses(fn () => $this->prepare(), [429]);
        $this->assertSame(50, AssistanceTurn::count());
        $this->assertSame(0, $this->calls);
    }
}
