<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\AssessmentMark;
use App\Models\Academics\TeachingAllocation;
use App\Models\Ai\AiUsage;
use App\Models\Growth\LearnerNote;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\TeachingDraft;
use App\Models\Learning\TeachingDraftVersion;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolModule;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Learning\TeachingDraftPhotos;
use App\Services\Learning\TeachingDraftRetention;
use App\Services\Learning\TeachingDrafts;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Support\ApiRoute;
use Tests\Support\OpenApiSpec;
use Tests\TestCase;

class TeachingDraftsTest extends TestCase
{
    use UsesPracticeScenario;

    private mixed $outputChange = null;

    private mixed $afterInput = null;

    private mixed $afterCompute = null;

    private bool $unknown = false;

    private int $completions = 0;

    private int $moderations = 0;

    private array $providerInputs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        foreach (['teaching_assistant', 'test_insights'] as $module) {
            SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true]);
        }
        config(['ai.inference.enabled' => true, 'ai.inference.features.teaching_assistant.enabled' => true,
            'ai.inference.features.teaching_assistant.model' => 'teaching-protocol-test',
            'ai.inference.features.teaching_assistant.cents_per_million_tokens' => 1000,
            'ai.providers.openai.qualified' => true, 'ai.providers.openai.key' => 'synthetic-test-only']);
        Storage::fake(TeachingDraftPhotos::DISK);
        $this->app->instance(UploadScanner::class, new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-test-scanner'];
            }
        });
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/moderations')) {
                $this->moderations++;
                if ($this->moderations === 1 && $this->afterInput !== null) {
                    ($this->afterInput)();
                }

                return Http::response(['results' => [['flagged' => false]]]);
            }
            $this->completions++;
            if ($this->unknown) {
                throw new ConnectionException('Synthetic unknown compute outcome');
            }
            $content = $request['messages'][1]['content'];
            $input = json_decode(is_string($content) ? $content : $content[0]['text'], true, 16, JSON_THROW_ON_ERROR);
            $this->providerInputs[] = $input;
            $definition = match ($input['kind']) {
                'report_comment' => ['text' => 'Continue explaining the steps using equal parts.'],
                'practice', 'follow_up_quiz' => ['title' => 'Fraction follow-up', 'questions' => [[
                    'topic_id' => $input['approved_topics'][0]['id'], 'estimated_seconds' => 120,
                    'definition' => ['kind' => 'number', 'prompt' => 'One half as a decimal?', 'explanation' => 'Divide one by two.', 'options' => [], 'answers' => ['0.5']],
                ]]],
                'script_marks' => ['question_marks' => array_map(fn ($q) => ['question_id' => $q['question_id'], 'mark' => '1.00', 'confidence_bp' => 8000, 'reason' => 'Provisional reading of the supplied working.'], $input['questions'])],
                default => ['title' => 'Fractions worksheet', 'sections' => array_map(fn ($level) => ['level' => $level, 'body' => 'Explain equal parts with a worked example.'], ['support', 'core', 'extension'])],
            };
            $output = ['subject_token' => $input['subject_token'], 'kind' => $input['kind'], 'definition' => $definition];
            if ($this->outputChange !== null) {
                $output = ($this->outputChange)($output);
            }
            if ($this->afterCompute !== null) {
                ($this->afterCompute)();
            }

            return Http::response(['model' => 'teaching-protocol-test', 'choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => json_encode($output)]]],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 40, 'total_tokens' => 160]]);
        });
    }

    private function input(array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'content_version_id' => $this->source, 'kind' => 'worksheet', 'language' => 'en',
            'brief' => 'Prepare differentiated fraction activities.', 'learner_id' => null, 'task_id' => null, 'photo_ids' => [], 'topic_ids' => []];
    }

    private function change(array $draft, string $action, ?array $definition = null, ?string $operation = null, bool $confirmed = false): array
    {
        return app(TeachingDrafts::class)->change($this->actor(), $draft['id'], ['operation_id' => $operation ?? (string) Str::uuid(),
            'expected_revision' => $draft['revision'], 'action' => $action, 'definition' => $definition, 'confirmed' => $confirmed]);
    }

    public function test_three_level_draft_replay_edits_and_approval_preserve_history_without_publishing(): void
    {
        $input = $this->input();
        $service = app(TeachingDrafts::class);
        $draft = $service->generate($this->actor(), $this->group, $input);
        $this->assertSame('ready', $draft['state']);
        $this->assertSame(['support', 'core', 'extension'], array_column($draft['definition']['sections'], 'level'));
        $this->assertSame($draft, $service->generate($this->actor(), $this->group, $input));
        $this->assertSame(1, $this->completions);
        $this->assertSame(1, AiUsage::count());
        $this->assertSame(0, PracticeItem::count());
        $edited = $draft['definition'];
        $edited['sections'][0]['body'] = 'Human reviewed support activity.';
        $operation = (string) Str::uuid();
        $result = $this->change($draft, 'edit', $edited, $operation);
        $this->assertSame($result, $this->change($draft, 'edit', $edited, $operation));
        $this->assertSame(2, $result['revision']);
        $approved = $this->change($result, 'approve');
        $this->assertSame('approved', $approved['state']);
        $view = $service->view($this->actor(), $draft['id']);
        $this->assertSame('Human reviewed support activity.', $view['definition']['sections'][0]['body']);
        $this->assertSame(3, TeachingDraftVersion::count());
        $this->assertStringNotContainsString('Human reviewed', TeachingDraftVersion::latest('id')->first()->getRawOriginal('definition'));
        $this->assertSame(0, AssessmentMark::count());
    }

    public function test_practice_is_added_only_after_separate_explicit_teacher_confirmation(): void
    {
        $draft = app(TeachingDrafts::class)->generate($this->actor(), $this->group, $this->input(['kind' => 'practice', 'topic_ids' => [$this->nodes[$this->keys[1]]]]));
        $this->assertSame('ready', $draft['state']);
        $this->assertSame(0, PracticeItem::count());
        $approved = $this->change($draft, 'approve');
        $this->refuses(fn () => $this->change($approved, 'publish_practice'), 422);
        $operation = (string) Str::uuid();
        $result = $this->change($approved, 'publish_practice', operation: $operation, confirmed: true);
        $this->assertSame('published', $result['state']);
        $this->assertSame($result, $this->change($approved, 'publish_practice', operation: $operation, confirmed: true));
        $this->assertSame(1, PracticeItem::count());
        $this->assertSame(0, AssessmentMark::count());
    }

    public function test_changed_requests_stale_edits_and_source_withdrawal_never_replay_old_private_bodies(): void
    {
        $input = $this->input();
        $service = app(TeachingDrafts::class);
        $draft = $service->generate($this->actor(), $this->group, $input);
        $this->refuses(fn () => $service->generate($this->actor(), $this->group, array_replace($input, ['brief' => 'Different requested work.'])), 409);
        $definition = $draft['definition'];
        $definition['title'] = 'Changed by the teacher';
        $this->change($draft, 'edit', $definition);
        $this->refuses(fn () => $this->change($draft, 'approve'), 409);
        $this->content->update(['active' => false]);
        $this->refuses(fn () => $service->view($this->actor(), $draft['id']), 404);
        $this->refuses(fn () => $service->generate($this->actor(), $this->group, $input), 404);
        $this->assertSame(1, $this->completions);
    }

    public function test_allocation_withdrawal_during_moderation_prevents_compute(): void
    {
        $this->afterInput = fn () => TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->refuses(fn () => app(TeachingDrafts::class)->generate($this->actor(), $this->group, $this->input()), 403);
        $this->assertSame(0, $this->completions);
        $this->assertSame(0, TeachingDraftVersion::count());
        $this->assertSame(0, AssessmentMark::count());
    }

    public function test_allocation_withdrawal_after_compute_hides_output_but_retains_compute_ledger(): void
    {
        $this->afterCompute = fn () => TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->refuses(fn () => app(TeachingDrafts::class)->generate($this->actor(), $this->group, $this->input()), 403);
        $this->assertSame(1, $this->completions);
        $this->assertSame(0, TeachingDraftVersion::count());
        $this->assertGreaterThan(0, AiUsage::sole()->cost_cents);
        $this->assertSame('failed', AiUsage::sole()->state);
    }

    public function test_unknown_compute_does_not_automatically_retry_or_double_charge(): void
    {
        $this->unknown = true;
        $input = $this->input();
        $service = app(TeachingDrafts::class);
        $draft = $service->generate($this->actor(), $this->group, $input);
        $this->assertSame('failed', $draft['state']);
        $this->assertNull($draft['definition']);
        $cost = AiUsage::sole()->cost_cents;
        $this->assertGreaterThan(0, $cost);
        $this->assertSame($draft, $service->generate($this->actor(), $this->group, $input));
        $this->assertSame(1, $this->completions);
        $this->assertSame(1, AiUsage::count());
        $this->assertSame($cost, AiUsage::sole()->cost_cents);
    }

    public function test_off_subject_response_is_quarantined_and_expired_feature_copies_are_erased(): void
    {
        $this->outputChange = fn ($output) => array_replace($output, ['subject_token' => str_repeat('0', 64)]);
        $draft = app(TeachingDrafts::class)->generate($this->actor(), $this->group, $this->input());
        $this->assertSame('quarantined', $draft['state']);
        $this->assertNull($draft['definition']);
        $this->assertNull($draft['request']);
        $this->assertSame('quarantined', AiUsage::sole()->state);
        $this->assertSame(0, TeachingDraftVersion::count());
        $this->travel(31)->days();
        app(AiQuarantine::class)->prune();
        $this->assertSame('purged', TeachingDraft::findOrFail($draft['id'])->state);
        $this->assertNull(TeachingDraft::findOrFail($draft['id'])->request);
        $this->assertSame(1, AiUsage::count());
        $this->assertGreaterThan(0, AiUsage::sole()->cost_cents);
    }

    public function test_report_comment_excludes_clinical_notes_and_minimizes_the_selected_learner(): void
    {
        foreach (['staff' => 'Asha should explain equal fraction parts.', 'counselling' => 'SECRET_CLINICAL_CANARY'] as $visibility => $body) {
            LearnerNote::create(['learner_id' => $this->schoolA->ids['asha'], 'subject_id' => $this->subject,
                'session_year_id' => $this->year, 'author_id' => $this->actor()->id, 'visibility' => $visibility, 'body' => $body]);
        }
        $draft = app(TeachingDrafts::class)->generate($this->actor(), $this->group, $this->input(['kind' => 'report_comment', 'learner_id' => $this->schoolA->ids['asha'], 'brief' => 'Draft a report comment for Asha.']));
        $this->assertSame('ready', $draft['state']);
        $input = json_encode($this->providerInputs, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('equal fraction parts', $input);
        $this->assertStringNotContainsString('Asha', $input);
        $this->assertStringNotContainsString('SECRET_CLINICAL_CANARY', $input);
        $this->assertStringNotContainsString('learner_id', $input);
        $this->assertNull($this->providerInputs[0]['released_marks']);
        $this->assertSame($this->schoolA->ids['asha'], (int) AiUsage::sole()->learner_id);
    }

    public function test_another_allocated_teacher_cannot_read_the_authors_draft(): void
    {
        $draft = app(TeachingDrafts::class)->generate($this->actor(), $this->group, $this->input());
        $other = $this->actor('theo');
        $other->forceFill(['password_change_required' => false])->save();
        TeachingAllocation::create(['teaching_group_id' => $this->group, 'teacher_id' => $this->schoolA->ids['theo_teacher'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic independent allocated teacher']);
        $this->refuses(fn () => app(TeachingDrafts::class)->view($other, $draft['id']), 404);
        $this->refuses(fn () => app(TeachingDrafts::class)->view($this->actor('farid'), $draft['id']), 403);
        $this->assertSame(1, $this->completions);
    }

    private function scriptPhoto(string $operation): array
    {
        $image = imagecreatetruecolor(240, 120);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagestring($image, 5, 2, 2, 'VISIBLE NAME CANARY', imagecolorallocate($image, 255, 0, 0));
        imagestring($image, 5, 2, 80, '1/2 = 0.5', imagecolorallocate($image, 0, 0, 255));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return app(TeachingDraftPhotos::class)->upload($this->actor(), $this->group, $this->schoolA->ids['asha'], $operation,
            UploadedFile::fake()->createWithContent('PRIVATE-PERSON-original.png', $bytes), ['x' => 0, 'y' => 60, 'width' => 240, 'height' => 60], true);
    }

    public function test_script_proposals_leave_official_marks_untouched_until_explicit_confirmed_application(): void
    {
        $operation = (string) Str::uuid();
        $photo = $this->scriptPhoto($operation);
        $preview = app(TeachingDraftPhotos::class)->preview($this->actor(), $photo['id']);
        $dimensions = getimagesizefromstring($preview['bytes']);
        $this->assertSame([240, 60], [$dimensions[0], $dimensions[1]]);
        $draft = app(TeachingDrafts::class)->generate($this->actor(), $this->group, $this->input(['operation_id' => $operation,
            'kind' => 'script_marks', 'learner_id' => $this->schoolA->ids['asha'], 'task_id' => $this->task, 'photo_ids' => [$photo['id']]]));
        $this->assertSame('ready', $draft['state']);
        $this->assertSame(0, AssessmentMark::count());
        $this->assertCount(2, $draft['definition']['question_marks']);
        $this->assertSame(8000, $draft['definition']['question_marks'][0]['confidence_bp']);
        $approved = $this->change($draft, 'approve');
        $this->assertSame(0, AssessmentMark::count());
        $this->refuses(fn () => $this->change($approved, 'confirm_marks'), 422);
        $confirm = (string) Str::uuid();
        $applied = $this->change($approved, 'confirm_marks', operation: $confirm, confirmed: true);
        $this->assertSame('applied', $applied['state']);
        $this->assertSame(1, AssessmentMark::count());
        $this->assertSame(200, AssessmentMark::sole()->mark_units);
        $this->assertSame($applied, $this->change($approved, 'confirm_marks', operation: $confirm, confirmed: true));
        $this->assertSame(1, AssessmentMark::sole()->revision);
        $this->assertSame('applied', app(TeachingDrafts::class)->view($this->actor(), $draft['id'])['state']);
        $this->assertStringNotContainsString('learner_id', json_encode($this->providerInputs, JSON_THROW_ON_ERROR));
    }

    public function test_a_new_official_mark_prevents_applying_an_older_script_proposal(): void
    {
        $operation = (string) Str::uuid();
        $photo = $this->scriptPhoto($operation);
        $draft = app(TeachingDrafts::class)->generate($this->actor(), $this->group, $this->input(['operation_id' => $operation,
            'kind' => 'script_marks', 'learner_id' => $this->schoolA->ids['asha'], 'task_id' => $this->task, 'photo_ids' => [$photo['id']]]));
        $approved = $this->change($draft, 'approve');
        $this->capture('5.00', '6.00');
        $this->refuses(fn () => $this->change($approved, 'confirm_marks', confirmed: true), 409);
        $this->assertSame(1100, AssessmentMark::sole()->mark_units);
        $this->assertSame(1, AssessmentMark::sole()->revision);
    }

    public function test_revoked_script_crop_prevents_compute_and_unapproved_learners_cannot_upload(): void
    {
        $operation = (string) Str::uuid();
        $photo = $this->scriptPhoto($operation);
        app(TeachingDraftPhotos::class)->revoke($this->actor(), $photo['id']);
        $this->refuses(fn () => app(TeachingDraftPhotos::class)->preview($this->actor(), $photo['id']), 404);
        $this->refuses(fn () => app(TeachingDrafts::class)->generate($this->actor(), $this->group, $this->input(['operation_id' => $operation,
            'kind' => 'script_marks', 'learner_id' => $this->schoolA->ids['asha'], 'task_id' => $this->task, 'photo_ids' => [$photo['id']]])), 404);
        $this->assertSame(0, $this->completions);
        $this->assertSame(0, TeachingDraft::count());
        LearnerApproval::query()->delete();
        $this->refuses(fn () => $this->scriptPhoto((string) Str::uuid()), 403);
    }

    public function test_expired_healthy_draft_is_inaccessible_and_pruning_leaves_official_records(): void
    {
        $draft = app(TeachingDrafts::class)->generate($this->actor(), $this->group, $this->input());
        $this->capture();
        $this->travel(91)->days();
        $this->refuses(fn () => app(TeachingDrafts::class)->view($this->actor(), $draft['id']), 404);
        app(TeachingDraftRetention::class)->prune();
        $this->assertSame('purged', TeachingDraft::findOrFail($draft['id'])->state);
        $this->assertNull(TeachingDraftVersion::firstOrFail()->definition);
        $this->assertSame(1, AssessmentMark::count());
        $this->assertSame(1, AiUsage::count());
    }

    private function signIn(string $name = 'tara'): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($this->schoolA->tokens[$name])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor($name)->id]);
    }

    private function typed(string $method, string $path, array $body = []): TestResponse
    {
        $response = $this->json($method, $path, $body)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        OpenApiSpec::assertResponseMatches($method, parse_url($path, PHP_URL_PATH), $response->baseResponse);

        return $response;
    }

    public function test_real_http_configuration_generation_receipt_review_and_approved_pdf(): void
    {
        $this->signIn();
        $base = '/api/v2/teaching-assistant';
        $this->typed('GET', $base.'/configuration')->assertJsonPath('data.groups.0.id', $this->group);
        $this->typed('GET', $base.'/groups/'.$this->group)->assertJsonPath('data.sources.0.id', $this->source);
        $this->typed('GET', $base.'/groups/'.$this->group.'/learners')->assertJsonPath('data.items.0.approved', true);
        $input = $this->input();
        $draft = $this->typed('POST', $base.'/groups/'.$this->group.'/drafts', $input)->json('data');
        $path = $base.'/drafts/'.$draft['id'];
        $this->typed('GET', $path)->assertJsonPath('data', $draft);
        $this->typed('GET', $base.'/operations/'.$input['operation_id'])->assertJsonPath('data', $draft);
        $this->typed('POST', $base.'/groups/'.$this->group.'/drafts', $input)->assertJsonPath('data', $draft);
        $this->assertSame(1, $this->completions);
        $this->typed('GET', $base.'/drafts?group_id='.$this->group)->assertJsonPath('data.items.0.id', $draft['id']);
        $this->get($path.'/pdf')->assertConflict();
        $review = ['operation_id' => (string) Str::uuid(), 'expected_revision' => $draft['revision'], 'action' => 'approve', 'definition' => null, 'confirmed' => false];
        $receipt = $this->typed('POST', $path.'/review', $review)->assertJsonPath('data.state', 'approved')->json('data');
        $this->typed('POST', $path.'/review', $review)->assertJsonPath('data', $receipt);
        $locale = app()->getLocale();
        $pdf = $this->get($path.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertSame($locale, app()->getLocale());
        $this->content->update(['active' => false]);
        $this->get($path.'/pdf')->assertNotFound()->assertDontSee('Fractions worksheet');
    }

    public function test_real_http_insights_are_independent_of_teaching_generation(): void
    {
        $this->signIn();
        $this->getJson('/api/v2/me/context')->assertOk()->assertJsonPath('data.feature_flags.teaching_assistant', true)->assertJsonPath('data.feature_flags.test_insights', true);
        SchoolModule::where('module', 'teaching_assistant')->update(['enabled' => false]);
        $this->getJson('/api/v2/me/context')->assertOk()->assertJsonPath('data.feature_flags.teaching_assistant', false)->assertJsonPath('data.feature_flags.test_insights', true);
        $this->typed('GET', '/api/v2/test-insights/configuration');
        $this->typed('GET', '/api/v2/test-insights/groups/'.$this->group)->assertJsonPath('data.sources', []);
        $this->typed('GET', '/api/v2/test-insights/groups/'.$this->group.'/tasks/'.$this->task)
            ->assertJsonPath('data.summary.has_question_evidence', false)->assertJsonPath('data.summary.questions.0.average_mark', null);
        $this->getJson('/api/v2/teaching-assistant/configuration')->assertNotFound();
        $this->assertSame(0, $this->completions);
    }

    public function test_real_http_photo_crop_preview_and_revocation_exact_replay(): void
    {
        $this->signIn();
        $path = '/api/v2/teaching-assistant';
        $upload = $this->post($path.'/groups/'.$this->group.'/photos', ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->schoolA->ids['asha'],
            'photo' => UploadedFile::fake()->image('script.png', 120, 80), 'approved' => '1', 'crop_x' => 0, 'crop_y' => 40, 'crop_width' => 120, 'crop_height' => 40], ['Accept' => 'application/json'])
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        OpenApiSpec::assertResponseMatches('POST', $path.'/groups/'.$this->group.'/photos', $upload->baseResponse);
        $photo = $upload->json('data');
        $bytes = $this->get($path.'/photos/'.$photo['id'])->assertOk()->assertHeader('Content-Type', 'image/png')->getContent();
        $this->assertSame([120, 40], array_slice(getimagesizefromstring($bytes), 0, 2));
        $body = ['operation_id' => (string) Str::uuid()];
        $receipt = $this->typed('POST', $path.'/photos/'.$photo['id'].'/revoke', $body)->json('data');
        $this->typed('POST', $path.'/photos/'.$photo['id'].'/revoke', $body)->assertJsonPath('data', $receipt);
        $this->get($path.'/photos/'.$photo['id'])->assertNotFound();
    }

    public function test_real_http_role_account_school_and_allocation_boundaries(): void
    {
        foreach (['asha', 'farid'] as $name) {
            $this->signIn($name);
            $this->getJson('/api/v2/teaching-assistant/configuration')->assertForbidden();
            $this->getJson('/api/v2/test-insights/configuration')->assertForbidden();
        }
        $this->signIn();
        $this->withHeader('X-Account-Id', (string) $this->actor('farid')->id)->getJson('/api/v2/teaching-assistant/configuration')->assertForbidden();
        $this->signIn();
        $this->withHeader('X-School-Id', '2')->getJson('/api/v2/teaching-assistant/groups/'.$this->group)->assertForbidden();
        $this->signIn();
        $draft = app(TeachingDrafts::class)->generate($this->actor(), $this->group, $this->input());
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->getJson('/api/v2/teaching-assistant/drafts/'.$draft['id'])->assertForbidden()->assertDontSee('Fractions worksheet');
        $this->getJson('/api/v2/teaching-assistant/operations/'.$draft['operation_id'])->assertForbidden();
    }

    public function test_every_teaching_http_operation_requires_authentication_and_private_errors(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $count = 0;
        foreach (ApiRoute::all(app('router')) as $route) {
            if (! str_starts_with($route->uri, 'api/v2/teaching-assistant/') && ! str_starts_with($route->uri, 'api/v2/test-insights/')) {
                continue;
            }
            $response = $this->json($route->method, $route->path())->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private');
            OpenApiSpec::assertResponseMatches($route->method, $route->path(), $response->baseResponse);
            $count++;
        }
        $this->assertSame(15, $count);
    }

    public function test_unused_crops_expire_before_scheduler_and_unknown_marks_cannot_be_confirmed(): void
    {
        $operation = (string) Str::uuid();
        $photo = $this->scriptPhoto($operation);
        $original = now()->copy();
        $this->travel(8)->days();
        $this->refuses(fn () => app(TeachingDraftPhotos::class)->preview($this->actor(), $photo['id']), 404);
        $this->travelTo($original);
        $this->outputChange = function ($output) {
            $output['definition']['question_marks'][0]['mark'] = null;

            return $output;
        };
        $draft = app(TeachingDrafts::class)->generate($this->actor(), $this->group, $this->input(['operation_id' => $operation,
            'kind' => 'script_marks', 'learner_id' => $this->schoolA->ids['asha'], 'task_id' => $this->task, 'photo_ids' => [$photo['id']]]));
        $approved = $this->change($draft, 'approve');
        $this->refuses(fn () => $this->change($approved, 'confirm_marks', confirmed: true), 422);
        $this->assertSame(0, AssessmentMark::count());
    }

    public function test_retention_reconciles_only_expired_own_school_orphan_derivatives(): void
    {
        $disk = Storage::disk(TeachingDraftPhotos::DISK);
        $prefix = app(CurrentSchool::class)->storagePath('teaching-draft-photos');
        $old = $prefix.'/'.Str::uuid().'.enc';
        $fresh = $prefix.'/'.Str::uuid().'.enc';
        $unknown = $prefix.'/unrecognized.txt';
        $other = app(CurrentSchool::class)->run(2, fn () => app(CurrentSchool::class)->storagePath('teaching-draft-photos').'/'.Str::uuid().'.enc');
        foreach ([$old, $fresh, $unknown, $other] as $path) {
            $disk->put($path, 'synthetic encrypted derivative');
            touch($disk->path($path), now('UTC')->subDays($path === $fresh ? 1 : 8)->getTimestamp());
        }
        $this->assertSame(1, app(TeachingDraftRetention::class)->prune());
        $disk->assertMissing($old);
        foreach ([$fresh, $unknown, $other] as $path) {
            $disk->assertExists($path);
        }
    }

    public function test_actual_http_budget_refusals_have_a_visible_allowance_message(): void
    {
        $this->signIn();
        foreach (['actor_daily_cap', 'learner_daily_cap', 'school_daily_cap'] as $reason) {
            $this->mock(TeachingDrafts::class, function ($mock) use ($reason) {
                $mock->shouldReceive('generate')->once()->andThrow(new AiUnavailable($reason));
            });
            $path = '/api/v2/teaching-assistant/groups/'.$this->group.'/drafts';
            $response = $this->postJson($path, $this->input())->assertStatus(429)
                ->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data', null);
            $this->assertStringContainsString('allowance', $response->json('message'));
            OpenApiSpec::assertResponseMatches('POST', $path, $response->baseResponse);
        }
        $this->mock(TeachingDrafts::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andThrow(new AiUnavailable('inference_disabled'));
        });
        $path = '/api/v2/teaching-assistant/groups/'.$this->group.'/drafts';
        $response = $this->postJson($path, $this->input())->assertStatus(503)->assertJsonPath('data', null);
        OpenApiSpec::assertResponseMatches('POST', $path, $response->baseResponse);
        $this->assertSame(0, $this->completions);
    }
}
