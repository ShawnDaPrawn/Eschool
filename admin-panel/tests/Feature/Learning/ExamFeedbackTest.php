<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\QuestionMark;
use App\Models\Academics\TeachingAllocation;
use App\Models\Ai\AiQuarantineSnapshot;
use App\Models\Ai\AiSchoolLimit;
use App\Models\Ai\AiUsage;
use App\Models\Learning\ExamAnswer;
use App\Models\Learning\ExamAnswerReview;
use App\Models\Learning\PastPaperVersion;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Learning\ExamAnswerAccess;
use App\Services\Learning\ExamAnswerQuarantineMedia;
use App\Services\Learning\ExamFeedback;
use App\Services\Learning\ExamWorkingPhotos;
use App\Services\Learning\PastPaperLibrary;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class ExamFeedbackTest extends TestCase
{
    use UsesPracticeScenario, ValidatesOpenApiResponses;

    private array $paper;

    private bool $invalidOutput = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'exam_coach'], ['enabled' => true, 'in_package' => true]);
        $data = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0, 'title' => 'Synthetic exam paper', 'framework' => 'school', 'paper_year' => 2025, 'paper_code' => 'P1', 'group_ids' => [$this->group],
            'licence' => ['kind' => 'school_owned', 'reference' => 'Synthetic original paper', 'statement' => 'Synthetic school rights attested for protocol tests only.', 'confirmed' => true],
            'questions' => [['number' => '1', 'prompt' => 'Explain one half.', 'memo' => 'Private memo: explain two equal parts and identify the denominator.', 'max_units' => 1000, 'topic_ids' => [$this->nodes[$this->keys[1]]]]]];
        $draft = app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, $data);
        $this->paper = app(PastPaperLibrary::class)->publish($this->actor(), $this->group, $draft['id'], $draft['version_id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1]);
        Storage::fake(ExamWorkingPhotos::DISK);
        $this->app->instance(UploadScanner::class, new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-protocol', 'signature' => null];
            }
        });
        config(['ai.inference.enabled' => true, 'ai.inference.features.exam_coach.enabled' => true, 'ai.inference.features.exam_coach.model' => 'protocol-test',
            'ai.inference.features.exam_coach.cents_per_million_tokens' => 1000, 'ai.providers.openai.qualified' => true, 'ai.providers.openai.key' => 'synthetic']);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/moderations')) {
                return Http::response(['results' => [['flagged' => false]]]);
            }
            $parts = $request['messages'][1]['content'];
            $input = json_decode($parts[0]['text'], true, 16, JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString('Asha', $parts[0]['text']);
            $this->assertCount(2, $parts);

            return Http::response(['model' => 'protocol-test', 'choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => json_encode([
                'question_token' => $input['question_token'], 'mark_units' => 500, 'confidence_basis_points' => $this->invalidOutput ? 10001 : 7300,
                'feedback' => 'Show which two parts are equal and explain your denominator.',
            ])]]], 'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 40, 'total_tokens' => 140]]);
        });
    }

    private function file(): UploadedFile
    {
        $image = imagecreatetruecolor(80, 60);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagestring($image, 2, 2, 2, 'Asha NAME', imagecolorallocate($image, 0, 0, 0));
        imagefilledrectangle($image, 0, 30, 79, 59, imagecolorallocate($image, 0, 180, 0));
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return UploadedFile::fake()->createWithContent('synthetic-working.png', $bytes);
    }

    private function upload(?string $operation = null): array
    {
        return app(ExamWorkingPhotos::class)->upload($this->actor('asha'), $this->group, $this->paper['version_id'], '1', $operation ?? (string) Str::uuid(), $this->file(),
            ['x' => 0, 'y' => 30, 'width' => 40, 'height' => 20], true, true);
    }

    public function test_explicit_crop_stores_only_private_pixels_and_exact_upload_retry(): void
    {
        $operation = (string) Str::uuid();
        $row = $this->upload($operation);
        $this->assertSame($row, $this->upload($operation));
        $this->assertSame(1, ExamAnswer::count());
        $photo = ExamAnswer::findOrFail($row['id']);
        $bytes = Crypt::decryptString(Storage::disk(ExamWorkingPhotos::DISK)->get($photo->path));
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $bytes);
        $image = imagecreatefromstring($bytes);
        $this->assertSame(40, imagesx($image));
        $this->assertSame(20, imagesy($image));
        $this->assertSame(0x00B400, imagecolorat($image, 0, 0));
        imagedestroy($image);
        $this->assertStringNotContainsString($bytes, Storage::disk(ExamWorkingPhotos::DISK)->get($photo->path));
        $this->assertCount(1, Storage::disk(ExamWorkingPhotos::DISK)->allFiles());
        $this->refuses(fn () => app(ExamWorkingPhotos::class)->upload($this->actor('asha'), $this->group, $this->paper['version_id'], '1', $operation, $this->file(), ['x' => 0, 'y' => 0, 'width' => 40, 'height' => 20], true, true), 409);
        Http::assertNothingSent();
    }

    public function test_memo_bound_vision_feedback_is_provisional_metered_and_teacher_confirmation_is_audited_without_mark_posting(): void
    {
        $row = $this->upload();
        $marks = QuestionMark::count();
        $feedback = app(ExamFeedback::class)->send($this->actor('asha'), $row['id'], 'I split a whole into two parts.');
        $this->assertSame('completed', $feedback['state']);
        $this->assertTrue($feedback['provisional']);
        $this->assertSame(7300, $feedback['response']['confidence_basis_points']);
        $this->assertSame(500, $feedback['response']['mark_units']);
        $this->assertSame($row, $this->upload($row['operation_id']));
        $this->assertSame($feedback, app(ExamFeedback::class)->send($this->actor('asha'), $row['id'], 'I split a whole into two parts.'));
        Http::assertSentCount(3);
        $usage = AiUsage::where('feature', 'exam_coach')->firstOrFail();
        $this->assertSame('exam_coach', $usage->prompt_key);
        $this->assertSame('1.0.0', $usage->prompt_version);
        $this->assertSame('USD', $usage->billing_currency);
        $this->assertSame(140, (int) $usage->used_units);
        $input = ['operation_id' => (string) Str::uuid(), 'expected_revision' => $feedback['revision'], 'mark_units' => 600, 'reason' => 'Teacher verified the reasoning and corrected the suggested units.'];
        $review = app(ExamFeedback::class)->review($this->actor(), $row['id'], $input);
        $this->assertSame($review, app(ExamFeedback::class)->review($this->actor(), $row['id'], $input));
        $this->assertSame(1, ExamAnswerReview::count());
        $fresh = app(ExamFeedback::class)->read($this->actor('asha'), $row['id']);
        $this->assertFalse($fresh['provisional']);
        $this->assertSame(600, $fresh['review']['mark_units']);
        $this->assertStringNotContainsString($input['reason'], ExamAnswerReview::firstOrFail()->getRawOriginal('reason'));
        $this->assertSame($marks, QuestionMark::count());
        $this->refuses(fn () => app(ExamFeedback::class)->review($this->actor(), $row['id'], array_replace($input, ['operation_id' => (string) Str::uuid()])), 409);
    }

    public function test_wrong_role_owner_school_and_changed_source_refuse_working_and_feedback(): void
    {
        $row = $this->upload();
        $this->refuses(fn () => app(ExamAnswerAccess::class)->image($this->actor('farid'), $row['id']), 404);
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => app(ExamAnswerAccess::class)->image($this->actor('asha'), $row['id'])), 404);
        $this->refuses(fn () => app(ExamFeedback::class)->review($this->actor('asha'), $row['id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'mark_units' => 10, 'reason' => 'Not a teacher review']), 403);
        app(PastPaperLibrary::class)->withdraw($this->actor(), $this->group, $this->paper['id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => 2, 'reason' => 'Synthetic rights withdrawal']);
        $this->refuses(fn () => app(ExamFeedback::class)->send($this->actor('asha'), $row['id'], 'Working'), 404);
        $this->refuses(fn () => app(ExamAnswerAccess::class)->image($this->actor('asha'), $row['id']), 404);
        Http::assertNothingSent();
    }

    public function test_invalid_confidence_quarantines_output_and_erases_it_from_student_teacher_and_private_preview(): void
    {
        $this->invalidOutput = true;
        $row = $this->upload();
        $result = app(ExamFeedback::class)->send($this->actor('asha'), $row['id'], 'Synthetic private working');
        $this->assertSame('quarantined', $result['state']);
        $this->assertNull($result['response']);
        $this->assertArrayNotHasKey('working', $result);
        $this->refuses(fn () => app(ExamAnswerAccess::class)->image($this->actor('asha'), $row['id']), 404);
        $this->refuses(fn () => app(ExamFeedback::class)->read($this->actor(), $row['id'], true), 404);
        $this->assertSame('quarantined', AiUsage::where('feature', 'exam_coach')->firstOrFail()->state);
        $this->assertSame(1, AiQuarantineSnapshot::count());
        $usage = AiUsage::where('feature', 'exam_coach')->firstOrFail();
        $reference = app(ExamAnswerAccess::class)->reference(ExamAnswer::findOrFail($row['id']));
        $this->assertSame([$reference], AiQuarantineSnapshot::firstOrFail()->private_content['media']);
        $reviewer = User::create(['first_name' => 'Synthetic', 'last_name' => 'Reviewer', 'email' => 'exam-output-reviewer@example.test', 'password' => Hash::make('Synthetic2026'), 'status' => 1]);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $reviewer->id, 'role' => 'Safeguarding', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $reviewer->assignRole('Safeguarding');
        $crop = app(ExamAnswerQuarantineMedia::class)->read($reviewer, $usage, $reference);
        $this->assertSame($reference['sha256'], hash('sha256', $crop['bytes']));
    }

    public function test_local_disclosure_is_quarantined_with_zero_external_calls_even_when_provider_is_off(): void
    {
        config(['ai.inference.enabled' => false]);
        $row = $this->upload();
        $result = app(ExamFeedback::class)->send($this->actor('asha'), $row['id'], 'I want to kill myself');
        $this->assertSame('quarantined', $result['state']);
        $this->assertSame('inference_quarantined', $result['failure_code']);
        $this->assertSame(0, (int) AiUsage::firstOrFail()->used_units);
        Http::assertNothingSent();
    }

    public function test_crop_approval_sharing_and_scanning_are_required_before_storage(): void
    {
        $call = fn ($approved, $sharing) => app(ExamWorkingPhotos::class)->upload($this->actor('asha'), $this->group, $this->paper['version_id'], '1', (string) Str::uuid(), $this->file(), ['x' => 0, 'y' => 30, 'width' => 40, 'height' => 20], $approved, $sharing);
        $this->refuses(fn () => $call(true, false), 422);
        try {
            $call(false, true);
            $this->fail('Explicit pixel crop approval is required.');
        } catch (AiUnavailable $error) {
            $this->assertSame('vision_crop_approval_required', $error->reason);
        }
        $this->app->instance(UploadScanner::class, new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => false, 'engine' => 'synthetic-rejected'];
            }
        });
        $this->refuses(fn () => $call(true, true), 422);
        $this->assertSame(0, ExamAnswer::count());
        $this->assertSame([], Storage::disk(ExamWorkingPhotos::DISK)->allFiles());
    }

    public function test_mid_scan_school_change_and_teacher_allocation_withdrawal_cannot_load_or_link_working(): void
    {
        $row = $this->upload();
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->refuses(fn () => app(ExamAnswerAccess::class)->image($this->actor(), $row['id'], true), 403);
        $this->app->instance(UploadScanner::class, new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                app(CurrentSchool::class)->set(2);

                return ['clean' => true, 'engine' => 'synthetic-switch'];
            }
        });
        $this->refuses(fn () => $this->upload(), 403);
        app(CurrentSchool::class)->set(1);
        $this->assertSame(1, ExamAnswer::count());
        $this->assertCount(1, Storage::disk(ExamWorkingPhotos::DISK)->allFiles());
    }

    public function test_only_fresh_safeguarding_retains_flagged_crop_and_expiry_purges_all_derived_working_without_metering_loss(): void
    {
        $row = $this->upload();
        app(ExamFeedback::class)->send($this->actor('asha'), $row['id'], 'I want to hurt myself');
        $usage = AiUsage::where('feature', 'exam_coach')->firstOrFail();
        $reviewer = User::create(['first_name' => 'Synthetic', 'last_name' => 'Reviewer', 'email' => 'exam-reviewer@example.test', 'password' => Hash::make('Synthetic2026'), 'status' => 1]);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $reviewer->id, 'role' => 'Safeguarding', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $reviewer->assignRole('Safeguarding');
        $ref = app(ExamAnswerAccess::class)->reference(ExamAnswer::findOrFail($row['id']));
        SchoolModule::where('module', 'exam_coach')->update(['enabled' => false]);
        $bytes = app(ExamAnswerQuarantineMedia::class)->read($reviewer, $usage, $ref);
        $this->assertSame('image/png', $bytes['mime']);
        $this->refuses(fn () => app(ExamAnswerQuarantineMedia::class)->read($this->actor(), $usage, $ref), 403);
        $this->refuses(fn () => app(ExamAnswerQuarantineMedia::class)->read($reviewer, $usage, array_replace($ref, ['sha256' => str_repeat('f', 64)])), 404);
        AiQuarantineSnapshot::query()->update(['expires_at' => now()->subMinute()]);
        $this->refuses(fn () => app(ExamAnswerQuarantineMedia::class)->read($reviewer, $usage, $ref), 404);
        $this->assertSame(1, app(AiQuarantine::class)->prune());
        $fresh = ExamAnswer::findOrFail($row['id']);
        $this->assertSame('purged', $fresh->state);
        $this->assertNull($fresh->working);
        $this->assertNull($fresh->path);
        $this->assertSame([], Storage::disk(ExamWorkingPhotos::DISK)->allFiles());
        $this->assertSame(1, AiUsage::count());
        $this->assertSame(1, PastPaperVersion::count());
    }

    public function test_revocation_deletes_own_private_crop_and_expired_working_never_reads(): void
    {
        $row = $this->upload();
        app(ExamWorkingPhotos::class)->revoke($this->actor('asha'), $row['id'], (string) Str::uuid());
        $this->assertSame([], Storage::disk(ExamWorkingPhotos::DISK)->allFiles());
        $this->refuses(fn () => app(ExamAnswerAccess::class)->image($this->actor('asha'), $row['id']), 404);
        $this->assertSame('purged', app(ExamFeedback::class)->read($this->actor('asha'), $row['id'])['state']);
        $next = $this->upload();
        ExamAnswer::findOrFail($next['id'])->update(['expires_at' => now('UTC')->subMinute()]);
        $this->refuses(fn () => app(ExamAnswerAccess::class)->image($this->actor('asha'), $next['id']), 404);
    }

    public function test_another_approved_current_student_cannot_read_or_revoke_the_owners_answer(): void
    {
        $row = $this->upload();
        $reader = $this->actor('ben');
        $reader->forceFill(['password_change_required' => false, 'status' => 1])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->actor('farid')->id],
            ['relationship' => 'father', 'status' => 'active', 'can_approve' => true]);
        LearnerApproval::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->actor('farid')->id],
            ['consent_version' => config('identity.consent_version'), 'consented_at' => now(), 'withdrawn_at' => null]);
        $this->refuses(fn () => app(ExamFeedback::class)->read($reader, $row['id']), 404);
        $this->refuses(fn () => app(ExamWorkingPhotos::class)->revoke($reader, $row['id'], (string) Str::uuid()), 404);
        $this->assertNull(ExamAnswer::findOrFail($row['id'])->revoked_at);
    }

    public function test_paused_school_returns_explicit_failure_without_dispatch_or_spending(): void
    {
        $row = $this->upload();
        AiSchoolLimit::create(['feature' => 'exam_coach', 'paused' => true, 'limits' => []]);
        $result = app(ExamFeedback::class)->send($this->actor('asha'), $row['id'], 'Synthetic bounded working');
        $this->assertSame('failed', $result['state']);
        $this->assertSame('school_inference_paused', $result['failure_code']);
        Http::assertNothingSent();
        $this->assertSame(0, (int) AiUsage::sum('cost_cents'));
        $this->assertSame(0, (int) AiUsage::sum('reserved_cost_cents'));
    }

    public function test_retention_erases_expired_crop_and_review_even_when_module_is_disabled_but_preserves_ledger_and_other_school(): void
    {
        $row = $this->upload();
        $feedback = app(ExamFeedback::class)->send($this->actor('asha'), $row['id'], 'Asha synthetic own reasoning');
        $review = app(ExamFeedback::class)->review($this->actor(), $row['id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => $feedback['revision'], 'mark_units' => 600, 'reason' => 'PRIVATE-REVIEW-RETENTION-CANARY']);
        $answer = ExamAnswer::findOrFail($row['id']);
        $this->assertSame(now('UTC')->addDays(30)->format('Y-m-d H:i:s'), $answer->expires_at->format('Y-m-d H:i:s'));
        $path = $answer->path;
        $answer->update(['expires_at' => now('UTC')->subMinute()]);
        SchoolModule::where('module', 'exam_coach')->update(['enabled' => false]);
        $other = 'schools/2/exam-working/'.Str::uuid().'.enc';
        Storage::disk(ExamWorkingPhotos::DISK)->put($other, 'OTHER-SCHOOL-OPAQUE-CANARY');
        $this->artisan('exam-coach:prune-working')->assertExitCode(0);
        Storage::disk(ExamWorkingPhotos::DISK)->assertMissing($path);
        Storage::disk(ExamWorkingPhotos::DISK)->assertExists($other);
        $this->assertSame('Expired private working review', ExamAnswerReview::findOrFail($review['id'])->reason);
        $this->assertNull($answer->refresh()->response);
        $this->assertSame(1, AiUsage::count());
    }

    private function signIn(string $name = 'asha'): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($this->schoolA->tokens[$name])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor($name)->id]);
    }

    private function uploadHttp(string $operation): array
    {
        return $this->post('/api/v2/exam-coach/groups/'.$this->group.'/versions/'.$this->paper['version_id'].'/answers',
            ['operation_id' => $operation, 'question_number' => '1', 'photo' => $this->file(), 'approved' => true, 'share_with_teacher' => true,
                'crop_x' => 0, 'crop_y' => 30, 'crop_width' => 40, 'crop_height' => 20], ['Accept' => 'application/json'])
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
    }

    public function test_real_http_upload_status_vision_and_teacher_review_are_private_and_recoverable(): void
    {
        $this->signIn();
        $base = '/api/v2/exam-coach';
        $operation = (string) Str::uuid();
        $receipt = $this->uploadHttp($operation);
        $this->assertSame($receipt, $this->uploadHttp($operation));
        $path = $base.'/answers/'.$receipt['id'];
        $this->getJson($base.'/answer-operations/'.$operation)->assertOk()->assertJsonPath('data.id', $receipt['id']);
        $this->getJson($base.'/answers')->assertOk()->assertJsonPath('data.answers.0.id', $receipt['id'])->assertJsonMissingPath('data.answers.0.working');
        $this->get($path.'/photo', ['Accept' => 'application/json'])->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('Referrer-Policy', 'no-referrer');
        $request = ['operation_id' => $operation, 'working' => ''];
        $feedback = $this->postJson($path.'/feedback', $request)->assertOk()->assertJsonPath('data.state', 'completed')->assertJsonPath('data.response.confidence_basis_points', 7300)->json('data');
        $this->postJson($path.'/feedback', $request)->assertOk()->assertJsonPath('data', $feedback);
        $this->getJson($path)->assertOk()->assertJsonPath('data.response.mark_units', 500)->assertDontSee('Private memo:');
        Http::assertSentCount(3);
        $this->signIn('tara');
        $staff = $base.'/staff/groups/'.$this->group;
        $this->getJson($staff.'/answers')->assertOk()->assertJsonPath('data.answers.0.id', $receipt['id'])->assertJsonMissingPath('data.answers.0.working');
        $this->getJson($staff.'/answers/'.$receipt['id'])->assertOk()->assertJsonPath('data.response.confidence_basis_points', 7300);
        $this->get($staff.'/answers/'.$receipt['id'].'/photo', ['Accept' => 'application/json'])->assertOk()->assertHeader('Content-Type', 'image/png');
        $input = ['operation_id' => (string) Str::uuid(), 'expected_revision' => $feedback['revision'], 'mark_units' => 600, 'reason' => 'PRIVATE-HTTP-TEACHER-REVIEW-CANARY'];
        $review = $this->postJson($staff.'/answers/'.$receipt['id'].'/review', $input)->assertOk()->json('data');
        $this->postJson($staff.'/answers/'.$receipt['id'].'/review', $input)->assertOk()->assertJsonPath('data', $review);
        $this->getJson($staff.'/operations/'.$input['operation_id'])->assertOk()->assertJsonPath('data.kind', 'answer_review')->assertJsonPath('data.review.id', $review['id'])->assertDontSee('PRIVATE-HTTP-TEACHER-REVIEW-CANARY');
        $this->signIn();
        $this->getJson($path)->assertOk()->assertJsonPath('data.review.mark_units', 600)->assertJsonPath('data.provisional', false);
        $revoke = ['operation_id' => (string) Str::uuid()];
        $this->postJson($path.'/revoke', $revoke)->assertOk()->assertJsonPath('data.state', 'purged');
        $this->postJson($path.'/revoke', $revoke)->assertOk()->assertJsonPath('data.state', 'purged');
        $this->get($path.'/photo', ['Accept' => 'application/json'])->assertNotFound();
    }

    public function test_http_output_quarantine_refuses_teacher_and_student_photos_without_restoring_private_input(): void
    {
        $this->signIn();
        $this->invalidOutput = true;
        $operation = (string) Str::uuid();
        $receipt = $this->uploadHttp($operation);
        $path = '/api/v2/exam-coach/answers/'.$receipt['id'];
        $this->postJson($path.'/feedback', ['operation_id' => $operation, 'working' => 'PRIVATE-HTTP-WORKING-CANARY'])->assertOk()->assertJsonPath('data.state', 'quarantined')->assertJsonPath('data.response', null)->assertDontSee('PRIVATE-HTTP-WORKING-CANARY');
        $this->getJson($path)->assertOk()->assertJsonMissingPath('data.working')->assertJsonPath('data.state', 'quarantined');
        $this->get($path.'/photo', ['Accept' => 'application/json'])->assertNotFound();
        $this->signIn('tara');
        $staff = '/api/v2/exam-coach/staff/groups/'.$this->group;
        $this->getJson($staff.'/answers')->assertOk()->assertJsonCount(0, 'data.answers');
        $this->getJson($staff.'/answers/'.$receipt['id'])->assertNotFound()->assertDontSee('PRIVATE-HTTP-WORKING-CANARY');
        $this->get($staff.'/answers/'.$receipt['id'].'/photo', ['Accept' => 'application/json'])->assertNotFound();
    }

    public function test_http_feedback_private_query_wrong_intent_parent_and_changed_account_are_refused(): void
    {
        $this->signIn();
        $receipt = $this->uploadHttp((string) Str::uuid());
        $path = '/api/v2/exam-coach/answers/'.$receipt['id'];
        $this->postJson($path.'/feedback', ['operation_id' => (string) Str::uuid(), 'working' => 'Synthetic'])->assertConflict();
        $this->postJson($path.'/feedback?working=PRIVATE-QUERY-CANARY', ['operation_id' => $receipt['operation_id'], 'working' => 'Synthetic'])->assertUnprocessable()->assertHeader('Referrer-Policy', 'no-referrer');
        $this->postJson($path.'/feedback', ['operation_id' => $receipt['operation_id'], 'working' => 'Synthetic', 'memo' => 'Injected'])->assertUnprocessable();
        $this->withHeader('X-Account-Id', (string) $this->actor('farid')->id)->getJson($path)->assertForbidden()->assertJsonMissingPath('data.working');
        $this->signIn('farid');
        $this->getJson($path)->assertNotFound();
        $this->postJson($path.'/feedback', ['operation_id' => $receipt['operation_id'], 'working' => 'Synthetic'])->assertNotFound();
        Http::assertNothingSent();
        $this->assertSame([], session()->getOldInput());
    }

    public function test_each_feedback_route_reaches_authentication_and_private_refusal_headers(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $u = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
        $base = '/api/v2/exam-coach';
        $staff = $base.'/staff/groups/1';
        foreach ([['GET', $staff.'/operations/'.$u], ['GET', $staff.'/answers'], ['GET', $staff.'/answers/'.$u], ['GET', $staff.'/answers/'.$u.'/photo'], ['POST', $staff.'/answers/'.$u.'/review'],
            ['POST', $base.'/groups/1/versions/'.$u.'/answers'], ['GET', $base.'/answer-operations/'.$u], ['GET', $base.'/answers'], ['GET', $base.'/answers/'.$u],
            ['GET', $base.'/answers/'.$u.'/photo'], ['POST', $base.'/answers/'.$u.'/feedback'], ['POST', $base.'/answers/'.$u.'/revoke']] as [$method, $path]) {
            $this->json($method, $path)->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    public function test_disabled_photo_feedback_is_explicit_while_licensed_papers_remain_available(): void
    {
        $this->signIn();
        $this->getJson('/api/v2/exam-coach/configuration')->assertOk()->assertJsonPath('data.photo_feedback_enabled', true);
        config(['ai.inference.features.exam_coach.enabled' => false]);
        $this->getJson('/api/v2/exam-coach/configuration')->assertOk()->assertJsonPath('data.photo_feedback_enabled', false);
        $this->getJson('/api/v2/exam-coach/groups/'.$this->group.'/papers')->assertOk()->assertJsonPath('data.papers.0.version_id', $this->paper['version_id']);
        Http::assertNothingSent();
    }

    public function test_recent_files_do_not_starve_an_older_orphan_beyond_the_first_scan_batch(): void
    {
        $row = $this->upload();
        $disk = Storage::disk(ExamWorkingPhotos::DISK);
        $prefix = app(CurrentSchool::class)->storagePath('exam-working');
        $active = ExamAnswer::findOrFail($row['id']);
        touch($disk->path($active->path), now('UTC')->subDays(2)->timestamp);
        for ($i = 0; $i < 200; $i++) {
            $id = '00000000-0000-4000-8000-'.str_pad((string) $i, 12, '0', STR_PAD_LEFT);
            $path = $prefix.'/'.$id.'.enc';
            $disk->put($path, 'RECENT-UNRESOLVED-WRITE-CANARY');
            touch($disk->path($path), now('UTC')->timestamp);
        }
        $orphan = $prefix.'/ffffffff-ffff-4fff-8fff-ffffffffffff.enc';
        $disk->put($orphan, 'EXPIRED-ORPHAN-CANARY');
        touch($disk->path($orphan), now('UTC')->subDays(2)->timestamp);
        app(ExamWorkingPhotos::class)->prune();
        $disk->assertMissing($orphan);
        $disk->assertExists($active->path);
        $this->assertNull($active->refresh()->purged_at);
        $this->assertCount(201, $disk->allFiles());
    }

    public function test_purged_audit_rows_do_not_retain_pixels_and_orphan_deletion_is_bounded(): void
    {
        $receipt = $this->upload();
        $row = ExamAnswer::findOrFail($receipt['id']);
        $disk = Storage::disk(ExamWorkingPhotos::DISK);
        $prefix = app(CurrentSchool::class)->storagePath('exam-working');
        $oldPath = $row->path;
        $row->update(['purged_at' => now('UTC'), 'path' => null]);
        touch($disk->path($oldPath), now('UTC')->subDays(2)->timestamp);
        for ($i = 0; $i < 200; $i++) {
            $path = $prefix.'/00000000-0000-4000-8000-'.str_pad((string) $i, 12, '0', STR_PAD_LEFT).'.enc';
            $disk->put($path, 'EXPIRED-ORPHAN');
            touch($disk->path($path), now('UTC')->subDays(2)->timestamp);
        }
        app(ExamWorkingPhotos::class)->prune();
        $this->assertCount(1, $disk->allFiles());
        app(ExamWorkingPhotos::class)->prune();
        $this->assertSame([], $disk->allFiles());
        $this->assertNotNull($row->refresh()->purged_at);
    }
}
