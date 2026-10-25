<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Ai\AiUsage;
use App\Models\Learning\TutorMessage;
use App\Models\Learning\TutorPhoto;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolModule;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Learning\TutorPhotos;
use App\Services\Platform\Files\UploadScanner;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class TutorApiTest extends TestCase
{
    use UsesPracticeScenario, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'tutor'], ['enabled' => true, 'in_package' => true]);
        config(['ai.inference.enabled' => true, 'ai.inference.features.tutor.enabled' => true, 'ai.inference.features.tutor.model' => 'protocol-test',
            'ai.inference.features.tutor.cents_per_million_tokens' => 1000, 'ai.providers.openai.qualified' => true, 'ai.providers.openai.key' => 'synthetic']);
        $this->app->instance(UploadScanner::class, new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic', 'signature' => null];
            }
        });
        Storage::fake(TutorPhotos::DISK);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/moderations')) {
                return Http::response(['results' => [['flagged' => false]]]);
            }
            $content = $request['messages'][1]['content'];
            $input = json_decode(is_array($content) ? $content[0]['text'] : $content, true, 16, JSON_THROW_ON_ERROR);

            return Http::response(['model' => 'protocol-test', 'choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => json_encode([
                'subject_token' => $input['subject_token'], 'kind' => $input['phase'] === 'hint' ? 'hint' : 'correction', 'text' => 'Start with equal parts.', 'question' => 'What would you try first?',
            ])]]], 'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 40, 'total_tokens' => 140]]);
        });
    }

    private function signIn(string $name = 'asha'): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($this->schoolA->tokens[$name])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor($name)->id]);
    }

    private function createSession(): array
    {
        return $this->postJson('/api/v2/tutor/sessions', ['operation_id' => (string) Str::uuid(), 'group_id' => $this->group, 'source_version_ids' => [$this->source]])
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
    }

    public function test_real_private_http_session_turn_receipt_and_parent_summary_match_typed_contract(): void
    {
        $this->signIn();
        $base = '/api/v2/tutor';
        $this->getJson($base.'/configuration')->assertOk()->assertJsonPath('data.reader_id', $this->actor('asha')->id)->assertJsonPath('data.groups.0.sources.0.id', $this->source);
        $session = $this->createSession();
        $input = ['operation_id' => (string) Str::uuid(), 'question' => 'How do I start with fractions?'];
        $path = $base.'/sessions/'.$session['id'];
        $first = $this->postJson($path.'/messages', $input)->assertOk()->assertJsonPath('data.state', 'completed')->assertJsonPath('data.response.kind', 'hint')->json('data');
        $this->postJson($path.'/messages', $input)->assertOk()->assertJsonPath('data', $first);
        $this->getJson($path.'/turns/'.$input['operation_id'])->assertOk()->assertJsonPath('data', $first);
        $this->getJson($path)->assertOk()->assertJsonPath('data.turns.0.id', $first['id']);
        $this->getJson($base.'/sessions')->assertOk()->assertJsonPath('data.sessions.0.id', $session['id']);
        Http::assertSentCount(3);
        $this->signIn('farid');
        $this->getJson($base.'/parent/learners/'.$this->schoolA->ids['asha'].'/subjects/'.$this->subject.'/summary')
            ->assertOk()->assertJsonPath('data.learning_steps', 1)->assertJsonPath('data.contains_raw_chat', false)->assertDontSee('How do I start');
        $this->getJson($path)->assertNotFound()->assertDontSee('equal parts');
        $this->signIn();
        $this->postJson($path.'/close', ['operation_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.state', 'closed');
        $this->postJson($path.'/messages', ['operation_id' => (string) Str::uuid(), 'question' => 'A new question'])->assertConflict();
    }

    public function test_real_private_scanned_crop_upload_preview_vision_and_revocation_are_typed(): void
    {
        $this->signIn();
        $session = $this->createSession();
        $operation = (string) Str::uuid();
        $image = imagecreatetruecolor(100, 60);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        $path = '/api/v2/tutor/sessions/'.$session['id'];
        $form = ['operation_id' => $operation, 'approved' => '1', 'crop_x' => '0', 'crop_y' => '0', 'crop_width' => '100', 'crop_height' => '60',
            'photo' => UploadedFile::fake()->createWithContent('PRIVATE-name.png', $bytes)];
        $photo = $this->post($path.'/photos', $form, ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.mime', 'image/png')->json('data');
        $this->getJson($path)->assertOk()->assertJsonPath('data.pending_photos.0.id', $photo['id'])->assertJsonPath('data.pending_photos.0.operation_id', $operation);
        $this->get($path.'/photos/'.$photo['id'], ['Accept' => 'image/png'])->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson($path.'/messages', ['operation_id' => $operation, 'question' => 'Please hint at the next step.', 'working' => 'I drew equal parts.', 'photo_ids' => [$photo['id']]])
            ->assertOk()->assertJsonPath('data.state', 'completed')->assertJsonPath('data.response.kind', 'correction');
        $this->getJson($path)->assertOk()->assertJsonPath('data.pending_photos', []);
        $this->postJson($path.'/photos/'.$photo['id'].'/revoke', ['operation_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.revoked', true);
        $this->getJson($path.'/photos/'.$photo['id'])->assertNotFound();
        $this->getJson($path)->assertNotFound()->assertDontSee('equal parts');
    }

    public function test_expected_account_school_role_guardian_and_source_are_checked_before_private_response(): void
    {
        $this->signIn();
        $session = $this->createSession();
        $path = '/api/v2/tutor/sessions/'.$session['id'];
        $this->withHeader('X-Account-Id', (string) $this->actor('farid')->id)->getJson($path)->assertForbidden();
        $this->signIn();
        $this->withHeader('X-School-Id', '2')->getJson($path)->assertForbidden();
        $this->signIn('tara');
        $this->getJson($path)->assertNotFound();
        $this->signIn('gina');
        $this->getJson('/api/v2/tutor/parent/learners/'.$this->schoolA->ids['asha'].'/subjects/'.$this->subject.'/summary')->assertNotFound();
        $this->signIn();
        LearnerApproval::where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $this->getJson($path)->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_private_writes_refuse_query_and_unknown_fields_without_flashing_drafts(): void
    {
        $this->signIn();
        $session = $this->createSession();
        $path = '/api/v2/tutor/sessions/'.$session['id'].'/messages';
        $input = ['operation_id' => (string) Str::uuid(), 'question' => 'PRIVATE-TUTOR-DRAFT'];
        $this->postJson($path.'?working=PRIVATE-QUERY', $input)->assertUnprocessable()->assertDontSee('PRIVATE-TUTOR-DRAFT')->assertSessionMissing('_old_input');
        $this->postJson($path, $input + ['learner_id' => $this->schoolA->ids['ben']])->assertUnprocessable()->assertDontSee('PRIVATE-TUTOR-DRAFT');
        $this->postJson($path, ['operation_id' => (string) Str::uuid(), 'question' => str_repeat('PRIVATE-', 200)])
            ->assertUnprocessable()->assertHeader('Cache-Control', 'no-store, private')->assertSessionMissing('_old_input');
        $this->assertSame(0, TutorMessage::count());
        Http::assertNothingSent();
    }

    public function test_caps_and_local_safeguarding_return_safe_explicit_operation_states(): void
    {
        $this->signIn();
        $session = $this->createSession();
        $path = '/api/v2/tutor/sessions/'.$session['id'].'/messages';
        config(['ai.inference.features.tutor.learner_daily_tokens' => 1]);
        $this->postJson($path, ['operation_id' => (string) Str::uuid(), 'question' => 'Help with fractions.'])
            ->assertOk()->assertJsonPath('data.state', 'failed')->assertJsonPath('data.failure_code', 'learner_daily_cap')->assertJsonPath('data.response', null);
        config(['ai.inference.enabled' => false]);
        $this->postJson($path, ['operation_id' => (string) Str::uuid(), 'question' => 'I want to hurt myself.'])
            ->assertOk()->assertJsonPath('data.state', 'quarantined')->assertJsonPath('data.question', null)->assertDontSee('I want to hurt myself');
        $this->assertSame(0, AiUsage::firstOrFail()->used_units);
        Http::assertNothingSent();
    }

    public function test_own_encrypted_human_draft_recovers_by_operation_without_parent_or_replacement_access(): void
    {
        $this->signIn();
        $session = $this->createSession();
        $operation = (string) Str::uuid();
        $image = imagecreatetruecolor(32, 32);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        $photo = app(TutorPhotos::class)->upload($this->actor('asha'), $session['id'], $operation,
            UploadedFile::fake()->createWithContent('working.png', $bytes), ['x' => 0, 'y' => 0, 'width' => 32, 'height' => 32], true);
        config(['ai.inference.enabled' => false]);
        $path = '/api/v2/tutor/sessions/'.$session['id'];
        $draft = ['operation_id' => $operation, 'question' => 'PRIVATE-SAVED-HUMAN-DRAFT', 'working' => 'My own unsubmitted working.', 'photo_ids' => [$photo['id']]];
        $this->postJson($path.'/messages', $draft)->assertOk()->assertJsonPath('data.state', 'failed')->assertJsonPath('data.question', $draft['question'])->assertJsonPath('data.response', null);
        $this->getJson($path.'/turns/'.$operation)->assertOk()->assertJsonPath('data.question', $draft['question'])->assertJsonPath('data.working', $draft['working']);
        $this->getJson($path)->assertOk()->assertJsonPath('data.pending_photos.0.id', $photo['id'])->assertJsonPath('data.pending_photos.0.operation_id', $operation);
        $this->signIn('farid');
        $this->getJson($path.'/turns/'.$operation)->assertNotFound()->assertDontSee($draft['question']);
        $this->signIn();
        $this->withHeader('X-Account-Id', (string) $this->actor('farid')->id);
        $this->getJson($path.'/turns/'.$operation)->assertForbidden()->assertDontSee($draft['question']);
        $this->signIn();
        LearnerApproval::where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $this->getJson($path.'/turns/'.$operation)->assertForbidden()->assertDontSee($draft['question']);
        Http::assertNothingSent();
    }

    public function test_pending_crop_capacity_preserves_exact_upload_recovery_and_releases_only_by_explicit_revoke(): void
    {
        $this->signIn();
        $session = $this->createSession();
        $image = imagecreatetruecolor(32, 32);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        $first = null;
        for ($i = 0; $i < 20; $i++) {
            $photo = app(TutorPhotos::class)->upload($this->actor('asha'), $session['id'], (string) Str::uuid(),
                UploadedFile::fake()->createWithContent('working.png', $bytes), ['x' => 0, 'y' => 0, 'width' => 32, 'height' => 32], true);
            $first ??= $photo;
        }
        $path = '/api/v2/tutor/sessions/'.$session['id'];
        $this->getJson($path)->assertOk()->assertJsonCount(20, 'data.pending_photos');
        $form = ['operation_id' => (string) Str::uuid(), 'approved' => '1', 'crop_x' => '0', 'crop_y' => '0', 'crop_width' => '32', 'crop_height' => '32',
            'photo' => UploadedFile::fake()->createWithContent('working.png', $bytes)];
        $this->post($path.'/photos', $form, ['Accept' => 'application/json'])->assertUnprocessable();
        $form['operation_id'] = $first['operation_id'];
        $this->post($path.'/photos', $form, ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.id', $first['id']);
        $this->postJson($path.'/photos/'.$first['id'].'/revoke', ['operation_id' => (string) Str::uuid()])->assertOk();
        $form['operation_id'] = (string) Str::uuid();
        $this->post($path.'/photos', $form, ['Accept' => 'application/json'])->assertOk();
        $this->assertSame(20, TutorPhoto::whereNull('revoked_at')->count());
        Http::assertNothingSent();
    }

    public function test_quarantined_operation_hides_its_approved_crop_preview_and_prepared_receipts(): void
    {
        $this->signIn();
        $session = $this->createSession();
        $operation = (string) Str::uuid();
        $image = imagecreatetruecolor(32, 32);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        $photo = app(TutorPhotos::class)->upload($this->actor('asha'), $session['id'], $operation,
            UploadedFile::fake()->createWithContent('working.png', $bytes), ['x' => 0, 'y' => 0, 'width' => 32, 'height' => 32], true);
        $path = '/api/v2/tutor/sessions/'.$session['id'];
        $this->getJson($path)->assertOk()->assertJsonPath('data.pending_photos.0.id', $photo['id']);
        $this->postJson($path.'/messages', ['operation_id' => $operation, 'question' => 'I am being abused.', 'photo_ids' => [$photo['id']]])
            ->assertOk()->assertJsonPath('data.state', 'quarantined')->assertJsonPath('data.question', null);
        $this->get($path.'/photos/'.$photo['id'], ['Accept' => 'image/png'])->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        // Usage quarantine must hide input even before a late message linkage.
        TutorMessage::where('operation_id', $operation)->update(['state' => 'pending', 'ai_usage_id' => null]);
        $this->getJson($path.'/turns/'.$operation)->assertOk()->assertJsonPath('data.state', 'quarantined')->assertJsonPath('data.question', null)->assertJsonPath('data.response', null);
        $this->getJson($path)->assertOk()->assertJsonPath('data.pending_photos', []);
        Http::assertNothingSent();
    }

    public function test_quarantine_created_during_private_crop_read_prevents_bytes_from_returning(): void
    {
        $this->signIn();
        $session = $this->createSession();
        $operation = (string) Str::uuid();
        $image = imagecreatetruecolor(32, 32);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        $photo = app(TutorPhotos::class)->upload($this->actor('asha'), $session['id'], $operation,
            UploadedFile::fake()->createWithContent('working.png', $bytes), ['x' => 0, 'y' => 0, 'width' => 32, 'height' => 32], true);
        $disk = Storage::disk(TutorPhotos::DISK);
        $adapter = \Mockery::mock(FilesystemAdapter::class);
        $adapter->shouldReceive('get')->once()->andReturnUsing(function ($path) use ($disk, $photo, $operation) {
            $usage = AiUsage::create(['user_id' => $this->actor('asha')->id, 'learner_id' => $this->schoolA->ids['asha'], 'feature' => 'tutor',
                'provider' => 'synthetic', 'operation_id' => $operation, 'request_hash' => hash('sha256', 'synthetic'),
                'usage_date' => now()->toDateString(), 'unit' => 'tokens', 'state' => 'reserved']);
            unset($photo['operation_id']);
            app(AiQuarantine::class)->capture($usage, 'input', ['safety'], ['text' => 'Synthetic held input', 'media' => [$photo]]);

            return $disk->get($path);
        });
        Storage::shouldReceive('disk')->with(TutorPhotos::DISK)->andReturn($adapter);
        $this->get('/api/v2/tutor/sessions/'.$session['id'].'/photos/'.$photo['id'], ['Accept' => 'image/png'])->assertNotFound();
    }
}
