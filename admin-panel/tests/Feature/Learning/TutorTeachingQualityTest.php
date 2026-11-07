<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\ContentVersion;
use App\Models\Ai\AiUsage;
use App\Models\Learning\TutorMemory as MemoryMark;
use App\Models\Learning\TutorMessage;
use App\Models\Platform\AuditLog;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Learning\TutorMemory;
use App\Services\Learning\TutorQueries;
use App\Services\Learning\TutorSessions;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/** Action Plan 3.10: tutor prompt v2, stage and grade, explain it back, memory, retrieval and home languages. */
class TutorTeachingQualityTest extends TestCase
{
    use UsesPracticeScenario, ValidatesOpenApiResponses;

    private const MARKDOWN = "# Fractions\nFRACTIONS-SECTION-CANARY Equal parts make a fraction.\n\n# Decimals\nDECIMALS-SECTION-CANARY A decimal point separates wholes from tenths.\n\n# Percentages\nPERCENT-SECTION-CANARY Per cent means out of a hundred.";

    /** @var list<array<string, mixed>> */
    private array $inputs = [];

    private mixed $outputChange = null;

    private bool $embeddingsFail = false;

    private int $embeddings = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'tutor'], ['enabled' => true, 'in_package' => true]);
        config(['ai.inference.enabled' => true, 'ai.inference.features.tutor.enabled' => true, 'ai.inference.features.tutor.model' => 'tutor-protocol-test',
            'ai.inference.features.tutor.cents_per_million_tokens' => 1000, 'ai.inference.features.tutor.prompt' => 'tutor-v2',
            'ai.inference.features.tutor_retrieval.model' => 'embedding-protocol-test', 'ai.inference.features.tutor_retrieval.cents_per_million_tokens' => 10,
            'ai.providers.openai.qualified' => true, 'ai.providers.openai.key' => 'synthetic-test-only', 'ai.tutor.home_languages' => []]);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        // The local fake provider: moderation, embeddings and chat answers; no real provider is called.
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/moderations')) {
                return Http::response(['results' => [['flagged' => false]]]);
            }
            if (str_ends_with($request->url(), '/embeddings')) {
                $this->embeddings++;
                if ($this->embeddingsFail) {
                    return Http::response(['error' => 'synthetic outage'], 500);
                }
                $text = strtolower((string) $request['input']);
                $vector = [substr_count($text, 'fraction') + 0.01, substr_count($text, 'decimal') + 0.01, substr_count($text, 'per cent') + substr_count($text, 'percent') + 0.01];

                return Http::response(['model' => 'embedding-protocol-test', 'data' => [['index' => 0, 'embedding' => $vector]], 'usage' => ['prompt_tokens' => 8, 'total_tokens' => 8]]);
            }
            $content = $request['messages'][1]['content'];
            $input = json_decode(is_string($content) ? $content : $content[0]['text'], true, 16, JSON_THROW_ON_ERROR);
            $this->inputs[] = $input + ['system' => $request['messages'][0]['content']];
            $output = ['subject_token' => $input['subject_token'], 'kind' => $input['phase'] === 'hint' ? 'hint' : 'correction',
                'text' => 'Well done for trying. Worked example: 1/4 + 2/4 = 3/4. Watch out: we add only the tops.', 'question' => 'Can you explain it back in your own words?'];
            if ($this->outputChange !== null) {
                $output = ($this->outputChange)($output);
            }

            return Http::response(['model' => 'tutor-protocol-test', 'choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => json_encode($output)]]],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 40, 'total_tokens' => 160]]);
        });
    }

    private function open(?int $topic = null): array
    {
        return app(TutorSessions::class)->create($this->actor('asha'), $this->group, [$this->source], (string) Str::uuid(), $topic);
    }

    private function turn(array $session, string $text = 'How do I start with fractions?', string $working = '', string $type = 'ask', ?string $language = null): array
    {
        return app(TutorSessions::class)->send($this->actor('asha'), $session['id'], (string) Str::uuid(), $text, $working, [], $type, $language);
    }

    private function context(int $turn = -1): array
    {
        $input = $turn < 0 ? end($this->inputs) : $this->inputs[$turn];

        return json_decode($input['approved_context'], true, 16, JSON_THROW_ON_ERROR);
    }

    private function sections(): void
    {
        // A synthetic published version with three headed sections (fixture set-up, before any session).
        $version = ContentVersion::findOrFail($this->source);
        $version->markdown = self::MARKDOWN;
        $version->saveQuietly();
    }

    private function retrieval(): void
    {
        config(['ai.inference.features.tutor_retrieval.enabled' => true]);
    }

    public function test_v2_turn_adapts_to_stage_and_grade_with_a_warm_structured_answer(): void
    {
        $turn = $this->turn($this->open());
        $this->assertSame(['completed', 'hint', 'ask', null], [$turn['state'], $turn['response']['kind'], $turn['turn_type'], $turn['explain_language']]);
        $this->assertSame(['subject_token', 'kind', 'text', 'question'], array_keys($turn['response']));
        $input = end($this->inputs);
        $this->assertStringContainsString('warm, patient', $input['system']);
        $this->assertSame(['hint', 'ask', null], [$input['phase'], $input['turn_type'], $input['explanation_language']]);
        $this->assertSame(['stage' => 'junior', 'grade' => 'Grade 7'], $this->context()['learner']);
        $this->assertNull($this->context()['memory']);
        $usage = AiUsage::where('feature', 'tutor')->sole();
        $this->assertSame(['tutor-v2', '2.0.0'], [$usage->prompt_key, $usage->prompt_version]);
        $this->assertSame('tutor-v2', TutorMessage::sole()->prompt_key);
        $this->assertStringNotContainsString('date_of_birth', json_encode($this->inputs));
        $config = app(TutorQueries::class)->configuration($this->actor('asha'));
        $this->assertSame([2, true, [], 90], [$config['tutor_version'], $config['explain_back_enabled'], $config['home_languages'], $config['memory_days']]);
    }

    public function test_v1_stays_selectable_with_its_original_context_and_refuses_v2_options(): void
    {
        config(['ai.inference.features.tutor.prompt' => 'tutor', 'ai.tutor.home_languages' => ['af']]);
        $this->retrieval();
        $this->sections();
        $session = $this->open();
        $turn = $this->turn($session, 'How do I compare decimals?');
        $this->assertSame(['completed', 'hint'], [$turn['state'], $turn['response']['kind']]);
        $input = end($this->inputs);
        $this->assertSame(['subject_token', 'subject', 'phase', 'approved_context', 'question', 'working', 'system'], array_keys($input));
        $this->assertSame(['curriculum', 'learning_guidance', 'mastery_priorities', 'recent_dialogue'], array_keys($this->context()));
        $this->assertSame([mb_strcut(self::MARKDOWN, 0, 3000, 'UTF-8')], $this->context()['curriculum']);
        $usage = AiUsage::where('feature', 'tutor')->sole();
        $this->assertSame(['tutor', '1.2.0'], [$usage->prompt_key, $usage->prompt_version]);
        $this->assertSame(0, $this->embeddings);
        $this->refuses(fn () => $this->turn($session, 'Let me explain.', type: 'explain_back'), 422);
        $this->refuses(fn () => $this->turn($session, 'Explain in Afrikaans.', language: 'af'), 422);
        $this->assertSame(1, app(TutorQueries::class)->configuration($this->actor('asha'))['tutor_version']);
        // Switching to v2 keeps earlier v1 turns readable.
        config(['ai.inference.features.tutor.prompt' => 'tutor-v2']);
        $this->assertSame('completed', app(TutorSessions::class)->read($this->actor('asha'), $session['id'])['turns'][0]['state']);
    }

    public function test_explain_back_turn_gets_feedback_on_the_learners_own_explanation(): void
    {
        $session = $this->open();
        $turn = $this->turn($session, 'You need equal parts so each piece is the same size.', type: 'explain_back');
        $this->assertSame(['completed', 'correction', 'explain_back'], [$turn['state'], $turn['response']['kind'], $turn['turn_type']]);
        $this->assertSame(['explain_back', 'explain_back'], [end($this->inputs)['phase'], end($this->inputs)['turn_type']]);
        // An ordinary question without working still never accepts a correction.
        $this->outputChange = fn ($output) => array_replace($output, ['kind' => 'correction']);
        $this->assertSame('quarantined', $this->turn($session)['state']);
        $this->assertSame('explain_back', $this->context()['recent_dialogue'][0]['turn_type']);
        $this->assertThrows(fn () => $this->turn($session, 'Hello', type: 'grade_me'), ValidationException::class);
    }

    public function test_home_language_explanations_are_limited_to_offered_and_enabled_languages(): void
    {
        $session = $this->open();
        $this->assertThrows(fn () => $this->turn($session, language: 'af'), ValidationException::class);
        config(['ai.tutor.home_languages' => ['af', 'zu', 'fr']]);
        $choices = app(TutorQueries::class)->configuration($this->actor('asha'))['home_languages'];
        $this->assertSame([['code' => 'af', 'name' => 'Afrikaans'], ['code' => 'zu', 'name' => 'isiZulu']], $choices);
        $this->assertThrows(fn () => $this->turn($session, language: 'fr'), ValidationException::class);
        $turn = $this->turn($session, language: 'zu');
        $this->assertSame(['completed', 'zu'], [$turn['state'], $turn['explain_language']]);
        $this->assertSame(['code' => 'zu', 'name' => 'isiZulu'], end($this->inputs)['explanation_language']);
        $this->assertStringContainsString('explanation_language', end($this->inputs)['system']);
        $this->assertSame(0, AiUsage::where('feature', 'tutor_retrieval')->count());
    }

    public function test_embeddings_choose_the_relevant_section_and_replay_section_vectors_in_the_session(): void
    {
        $this->sections();
        $this->retrieval();
        $session = $this->open();
        $turn = $this->turn($session, 'How do I compare decimals?');
        $this->assertSame('completed', $turn['state']);
        $curriculum = implode("\n", $this->context()['curriculum']);
        $this->assertStringContainsString('DECIMALS-SECTION-CANARY', $curriculum);
        $this->assertStringNotContainsString('FRACTIONS-SECTION-CANARY', $curriculum);
        $this->assertSame(['method' => 'embeddings', 'index' => 1, 'count' => 3], array_intersect_key(TutorMessage::sole()->source_manifest['versions'][0]['section'], array_flip(['method', 'index', 'count'])));
        $this->assertSame(4, $this->embeddings);
        $usage = AiUsage::where('feature', 'tutor_retrieval')->get();
        $this->assertCount(4, $usage);
        $this->assertSame([$this->schoolA->ids['asha']], $usage->pluck('learner_id')->unique()->values()->all());
        $this->assertSame(['completed'], $usage->pluck('state')->unique()->values()->all());
        // A later question re-embeds only the question; section vectors replay from their receipts.
        $this->turn($session, 'What does per cent mean?');
        $this->assertSame(5, $this->embeddings);
        $this->assertStringContainsString('PERCENT-SECTION-CANARY', implode("\n", $this->context()['curriculum']));
        // The earlier turn is reused as dialogue although a different section was chosen.
        $this->assertCount(1, $this->context()['recent_dialogue']);
        Http::assertNotSent(fn ($request) => str_contains(json_encode($request->data()), 'Jacaranda'));
    }

    public function test_retrieval_falls_back_to_the_opening_when_off_failing_or_for_a_disclosure(): void
    {
        $this->sections();
        $session = $this->open();
        $this->turn($session, 'How do I compare decimals?');
        $this->assertSame(0, $this->embeddings);
        $this->assertSame([mb_strcut(self::MARKDOWN, 0, 3000, 'UTF-8')], $this->context()['curriculum']);
        $this->assertSame('opening', TutorMessage::sole()->source_manifest['versions'][0]['section']['method']);
        $this->retrieval();
        $this->embeddingsFail = true;
        $turn = $this->turn($session, 'And decimals again?');
        $this->assertSame('completed', $turn['state']);
        $this->assertSame(1, $this->embeddings);
        $this->assertStringContainsString('FRACTIONS-SECTION-CANARY', implode("\n", $this->context()['curriculum']));
        $this->assertSame('failed', AiUsage::where('feature', 'tutor_retrieval')->sole()->state);
        config(['ai.inference.features.tutor_retrieval.learner_daily_cents' => 0]);
        $this->embeddingsFail = false;
        $this->assertSame('completed', $this->turn($session, 'Decimals once more?')['state']);
        $this->assertSame(1, $this->embeddings);
        config(['ai.inference.features.tutor_retrieval.learner_daily_cents' => 50]);
        $sent = count(Http::recorded());
        $this->assertSame('quarantined', $this->turn($session, 'I want to hurt myself, decimals.')['state']);
        $this->assertCount($sent, Http::recorded());
    }

    public function test_memory_summarises_earlier_sessions_for_learner_guardian_and_the_next_v2_turn(): void
    {
        $first = $this->open($this->nodes[$this->keys[1]]);
        $this->turn($first, 'PRIVATE-OWN-QUESTION about fractions.');
        $this->turn($first, 'Equal parts matter.', type: 'explain_back');
        $own = app(TutorMemory::class)->own($this->actor('asha'));
        $this->assertSame([['subject_id' => $this->subject, 'subject_name' => $own['subjects'][0]['subject_name'], 'sessions' => 1, 'learning_steps' => 2, 'explained_back' => 1,
            'topics' => [['id' => $this->nodes[$this->keys[1]], 'name' => 'Fractions']], 'last_active_on' => '2026-02-10']], $own['subjects']);
        $this->assertSame([90, null, false], [$own['retention_days'], $own['cleared_at'], $own['contains_raw_chat']]);
        $this->assertSame($own['subjects'], app(TutorMemory::class)->forGuardian($this->actor('farid'), $this->schoolA->ids['asha'])['subjects']);
        $this->assertStringNotContainsString('PRIVATE-OWN-QUESTION', json_encode($own));
        $second = $this->open();
        $this->turn($second, 'Next step?');
        $memory = $this->context()['memory'];
        $this->assertSame(['earlier_sessions' => 1, 'learning_steps' => 2, 'explained_back' => 1, 'topics_practised' => ['Fractions'], 'last_active_on' => '2026-02-10'], $memory);
        $this->assertStringNotContainsString('PRIVATE-OWN-QUESTION', json_encode($memory));
        $this->assertNotNull(TutorMessage::latest('created_at')->latest('id')->get()->firstWhere('tutor_session_id', $second['id'])->source_manifest['memory']);
        // The memory stays visible while the school has the tutor switched off.
        SchoolModule::where('module', 'tutor')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        $this->assertCount(1, app(TutorMemory::class)->own($this->actor('asha'))['subjects']);
    }

    public function test_memory_never_includes_quarantined_or_flagged_turns(): void
    {
        $disclosure = $this->open($this->nodes[$this->keys[2]]);
        $this->assertSame('quarantined', $this->turn($disclosure, 'I am being abused.')['state']);
        $flaggedOutput = $this->open($this->nodes[$this->keys[2]]);
        $this->outputChange = fn ($output) => array_replace($output, ['subject_token' => str_repeat('a', 64)]);
        $this->assertSame('quarantined', $this->turn($flaggedOutput)['state']);
        $this->outputChange = null;
        $later = $this->open($this->nodes[$this->keys[2]]);
        $completed = $this->turn($later);
        $this->assertSame('completed', $completed['state']);
        AiUsage::where('operation_id', $completed['operation_id'])->update(['state' => 'quarantined']);
        $memory = app(TutorMemory::class)->own($this->actor('asha'));
        $this->assertSame([], $memory['subjects']);
        $this->assertStringNotContainsString('Decimals', json_encode($memory));
        $this->turn($this->open());
        $this->assertNull($this->context()['memory']);
    }

    public function test_learner_and_guardian_can_clear_memory_and_retention_drops_old_turns(): void
    {
        $this->turn($this->open());
        $cleared = app(TutorMemory::class)->clearOwn($this->actor('asha'), (string) Str::uuid());
        $this->assertSame([], $cleared['subjects']);
        $this->assertNotNull($cleared['cleared_at']);
        $this->assertSame('learner', MemoryMark::sole()->cleared_by_role);
        $this->assertTrue(AuditLog::where('action', 'tutor.memory_cleared')->exists());
        $this->travel(1)->minutes();
        $this->turn($this->open());
        $this->assertSame(1, app(TutorMemory::class)->own($this->actor('asha'))['subjects'][0]['learning_steps']);
        $this->assertSame([], app(TutorMemory::class)->clearForGuardian($this->actor('farid'), $this->schoolA->ids['asha'], (string) Str::uuid())['subjects']);
        $this->assertSame('guardian', MemoryMark::sole()->cleared_by_role);
        $this->travel(1)->minutes();
        $this->turn($this->open());
        $this->assertCount(1, app(TutorMemory::class)->own($this->actor('asha'))['subjects']);
        $this->travel(91)->days();
        $this->assertSame([], app(TutorMemory::class)->own($this->actor('asha'))['subjects']);
    }

    public function test_wrong_role_wrong_owner_and_wrong_school_cannot_read_or_clear_memory(): void
    {
        $this->turn($this->open());
        $memory = app(TutorMemory::class);
        $asha = $this->schoolA->ids['asha'];
        // Wrong role: a teacher has no memory of their own and no guardian view.
        $this->refuses(fn () => $memory->own($this->actor('tara')), 404);
        $this->refuses(fn () => $memory->forGuardian($this->actor('tara'), $asha), 404);
        $this->refuses(fn () => $memory->clearForGuardian($this->actor('tara'), $asha, (string) Str::uuid()), 404);
        // Wrong owner: another learner and another learner's guardian.
        $ben = $this->actor('ben');
        $ben->forceFill(['password_change_required' => false, 'status' => 1])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->actor('farid')->id], ['status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->actor('farid')->id,
            'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        $this->assertSame([], $memory->own($ben)['subjects']);
        $this->refuses(fn () => $memory->forGuardian($ben, $asha), 404);
        $this->refuses(fn () => $memory->clearForGuardian($ben, $asha, (string) Str::uuid()), 404);
        $memory->clearOwn($ben, (string) Str::uuid());
        $this->refuses(fn () => $memory->forGuardian($this->actor('gina'), $asha), 404);
        $this->refuses(fn () => $memory->clearForGuardian($this->actor('gina'), $asha, (string) Str::uuid()), 404);
        $this->assertCount(1, $memory->own($this->actor('asha'))['subjects']);
        $this->assertSame(0, MemoryMark::where('learner_id', $asha)->count());
        // Wrong school: School B cannot reach School A's learner or memory.
        $baobabParent = app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['farid_user']));
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => $memory->forGuardian($baobabParent, $asha)), 404);
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => $memory->clearForGuardian($baobabParent, $asha, (string) Str::uuid())), 404);
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => $memory->own($this->actor('asha'))), 404);
        $memory->clearOwn($this->actor('asha'), (string) Str::uuid());
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => MemoryMark::count()));
    }

    public function test_memory_and_teaching_options_over_http_match_the_typed_contract(): void
    {
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['asha'])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor('asha')->id]);
        $config = $this->getJson('/api/v2/tutor/configuration')->assertOk()->assertJsonPath('data.tutor_version', 2)->json('data');
        $this->assertTrue($config['explain_back_enabled']);
        $session = $this->postJson('/api/v2/tutor/sessions', ['operation_id' => (string) Str::uuid(), 'group_id' => $this->group, 'source_version_ids' => [$this->source]])->assertOk()->json('data');
        $this->postJson('/api/v2/tutor/sessions/'.$session['id'].'/messages', ['operation_id' => (string) Str::uuid(), 'question' => 'Equal parts are the same size.', 'turn_type' => 'explain_back'])
            ->assertOk()->assertJsonPath('data.turn_type', 'explain_back')->assertJsonPath('data.response.kind', 'correction');
        $this->postJson('/api/v2/tutor/sessions/'.$session['id'].'/messages', ['operation_id' => (string) Str::uuid(), 'question' => 'Hi', 'language' => 'af'])->assertUnprocessable();
        $this->getJson('/api/v2/tutor/memory')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.subjects.0.explained_back', 1);
        $this->postJson('/api/v2/tutor/memory/clear', ['operation_id' => (string) Str::uuid(), 'learner_id' => 99])->assertUnprocessable();
        $this->postJson('/api/v2/tutor/memory/clear', ['operation_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.subjects', []);
        $path = '/api/v2/tutor/parent/learners/'.$this->schoolA->ids['asha'].'/memory';
        $this->getJson($path)->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($this->schoolA->tokens['farid'])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor('farid')->id]);
        $this->getJson($path)->assertOk()->assertJsonPath('data.learner_id', $this->schoolA->ids['asha'])->assertDontSee('Equal parts');
        $this->postJson($path.'/clear', ['operation_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.contains_raw_chat', false);
        $this->withHeader('X-School-Id', '2')->getJson($path)->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($this->schoolA->tokens['gina'])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor('gina')->id]);
        $this->getJson($path)->assertNotFound();
        $this->postJson($path.'/clear', ['operation_id' => (string) Str::uuid()])->assertNotFound();
    }

    public function test_identical_sections_get_their_own_vectors_and_still_use_embeddings(): void
    {
        $version = ContentVersion::findOrFail($this->source);
        $version->markdown = "# Practice\nSame practice line.\n\n# Decimals\nDECIMALS-SECTION-CANARY A decimal point separates wholes from tenths.\n\n# Practice\nSame practice line.";
        $version->saveQuietly();
        $this->retrieval();
        $this->assertSame('completed', $this->turn($this->open(), 'How do I compare decimals?')['state']);
        $section = TutorMessage::sole()->source_manifest['versions'][0]['section'];
        $this->assertSame(['embeddings', 1, 3], [$section['method'], $section['index'], $section['count']]);
        $this->assertSame(4, $this->embeddings);
        $usage = AiUsage::where('feature', 'tutor_retrieval')->get();
        $this->assertSame([4, ['completed']], [$usage->pluck('operation_id')->unique()->count(), $usage->pluck('state')->unique()->values()->all()]);
    }

    private function signInAs(string $name): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($this->schoolA->tokens[$name])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor($name)->id]);
    }

    public function test_a_retried_clear_replays_without_erasing_newer_turns_on_learner_and_guardian_routes(): void
    {
        $audits = fn () => AuditLog::where('action', 'tutor.memory_cleared')->count();
        $this->turn($this->open());
        $own = ['operation_id' => (string) Str::uuid()];
        $this->signInAs('asha');
        $first = $this->postJson('/api/v2/tutor/memory/clear', $own)->assertOk()->json('data');
        $this->assertSame([], $first['subjects']);
        $this->travel(1)->minutes();
        $this->turn($this->open());
        $this->signInAs('asha');
        $replay = $this->postJson('/api/v2/tutor/memory/clear', $own)->assertOk()->json('data');
        $this->assertSame($first['cleared_at'], $replay['cleared_at']);
        $this->assertSame(1, $replay['subjects'][0]['learning_steps']);
        $this->assertSame(1, $audits());
        $path = '/api/v2/tutor/parent/learners/'.$this->schoolA->ids['asha'].'/memory/clear';
        $this->signInAs('farid');
        // Another person's clear operation is never replayed or reused.
        $this->postJson($path, $own)->assertStatus(409);
        $guardian = ['operation_id' => (string) Str::uuid()];
        $cleared = $this->postJson($path, $guardian)->assertOk()->json('data');
        $this->assertSame([], $cleared['subjects']);
        $this->assertNotSame($first['cleared_at'], $cleared['cleared_at']);
        $this->travel(1)->minutes();
        $this->turn($this->open());
        $this->signInAs('farid');
        $again = $this->postJson($path, $guardian)->assertOk()->json('data');
        $this->assertSame($cleared['cleared_at'], $again['cleared_at']);
        $this->assertSame(1, $again['subjects'][0]['learning_steps']);
        // Replaying the learner's older clear does not move the guardian's later one back or forward.
        $this->signInAs('asha');
        $this->assertSame($cleared['cleared_at'], $this->postJson('/api/v2/tutor/memory/clear', $own)->assertOk()->json('data.cleared_at'));
        $this->assertSame(2, $audits());
    }
}
