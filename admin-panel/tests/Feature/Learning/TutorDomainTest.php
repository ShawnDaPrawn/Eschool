<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingAllocation;
use App\Models\Ai\AiFlag;
use App\Models\Ai\AiQuarantineSnapshot;
use App\Models\Ai\AiUsage;
use App\Models\Growth\LearnerNote;
use App\Models\Learning\TutorMessage;
use App\Models\Learning\TutorPhoto;
use App\Models\Learning\TutorSession;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\Quarantine\AiFlagReviews;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Growth\LearnerNotes;
use App\Services\Learning\TutorAccess;
use App\Services\Learning\TutorFeaturePolicy;
use App\Services\Learning\TutorPhotos;
use App\Services\Learning\TutorQuarantineMedia;
use App\Services\Learning\TutorQueries;
use App\Services\Learning\TutorSessions;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

class TutorDomainTest extends TestCase
{
    use UsesPracticeScenario;

    private mixed $outputChange = null;

    private mixed $afterInput = null;

    private bool $networkFails = false;

    private int $moderations = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'tutor'], ['enabled' => true, 'in_package' => true]);
        config(['ai.inference.enabled' => true, 'ai.inference.features.tutor.enabled' => true, 'ai.inference.features.tutor.model' => 'tutor-protocol-test',
            'ai.inference.features.tutor.cents_per_million_tokens' => 1000, 'ai.providers.openai.qualified' => true, 'ai.providers.openai.key' => 'synthetic-test-only']);
        Storage::fake(TutorPhotos::DISK);
        $this->app->instance(UploadScanner::class, new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-test-scanner', 'signature' => null];
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
            if ($this->networkFails) {
                throw new ConnectionException('synthetic unknown-outcome fixture');
            }
            $content = $request['messages'][1]['content'];
            $input = json_decode(is_string($content) ? $content : $content[0]['text'], true, 16, JSON_THROW_ON_ERROR);
            $output = ['subject_token' => $input['subject_token'], 'kind' => $input['phase'] === 'hint' ? 'hint' : 'correction',
                'text' => 'Use equal parts and check your working.', 'question' => 'Which operation would you try next?'];
            if ($this->outputChange !== null) {
                $output = ($this->outputChange)($output);
            }

            return Http::response(['model' => 'tutor-protocol-test', 'choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => json_encode($output)]]],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 40, 'total_tokens' => 160]]);
        });
    }

    private function openTutor(?string $operation = null): array
    {
        return app(TutorSessions::class)->create($this->actor('asha'), $this->group, [$this->source], $operation ?? (string) Str::uuid());
    }

    private function turn(array $session, ?string $operation = null, string $text = 'How can I start this fractions exercise?', string $working = '', array $photos = []): array
    {
        return app(TutorSessions::class)->send($this->actor('asha'), $session['id'], $operation ?? (string) Str::uuid(), $text, $working, $photos);
    }

    private function sourcePhoto(): UploadedFile
    {
        $image = imagecreatetruecolor(240, 120);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagestring($image, 5, 2, 2, 'VISIBLE NAME CANARY', imagecolorallocate($image, 255, 0, 0));
        imagestring($image, 5, 2, 80, '1/2 = 0.5', imagecolorallocate($image, 0, 0, 255));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return UploadedFile::fake()->createWithContent('PRIVATE-PERSON-original.png', $bytes);
    }

    private function photo(array $session, string $operation, bool $approved = true): array
    {
        return app(TutorPhotos::class)->upload($this->actor('asha'), $session['id'], $operation, $this->sourcePhoto(), ['x' => 0, 'y' => 60, 'width' => 240, 'height' => 60], $approved);
    }

    public function test_own_subject_session_and_hint_replay_are_exact_encrypted_and_provisional(): void
    {
        $operation = (string) Str::uuid();
        $session = $this->openTutor($operation);
        $this->assertSame($session, $this->openTutor($operation));
        $turnOperation = (string) Str::uuid();
        $official = DB::table('question_marks')->count();
        $first = $this->turn($session, $turnOperation);
        $this->assertSame('completed', $first['state']);
        $this->assertSame('hint', $first['response']['kind']);
        $this->assertTrue($first['provisional']);
        $this->assertSame($first, $this->turn($session, $turnOperation));
        Http::assertSentCount(3);
        $this->assertSame(1, TutorMessage::count());
        $this->assertSame(1, AiUsage::where('feature', 'tutor')->count());
        $this->assertSame($official, DB::table('question_marks')->count());
        $this->assertStringNotContainsString('fractions exercise', DB::table('tutor_messages')->value('learner_text'));
        $this->assertSame($this->schoolA->ids['asha'], (int) AiUsage::firstOrFail()->learner_id);
        $this->refuses(fn () => $this->turn($session, $turnOperation, 'Changed question'), 409);
    }

    public function test_teacher_tutor_guidance_only_and_known_identifiers_are_minimized_before_provider(): void
    {
        app(LearnerNotes::class)->create($this->actor(), $this->schoolA->ids['asha'], $this->subject, $this->year, 'tutor', 'TUTOR-SCAFFOLD: ask for equal parts.');
        foreach (['staff', 'counselling'] as $kind) {
            LearnerNote::create(['learner_id' => $this->schoolA->ids['asha'], 'subject_id' => $this->subject, 'session_year_id' => $this->year,
                'author_id' => $this->actor()->id, 'visibility' => $kind, 'body' => 'PRIVATE-'.$kind.'-CANARY']);
        }
        $session = $this->openTutor();
        $result = $this->turn($session, text: 'I am Asha. Email private@example.test. Help with fractions.');
        $this->assertSame('completed', $result['state']);
        Http::assertSent(function ($request) {
            if (! str_ends_with($request->url(), '/chat/completions')) {
                return false;
            }
            $body = json_encode($request->data());
            $this->assertStringContainsString('TUTOR-SCAFFOLD', $body);
            foreach (['PRIVATE-staff-CANARY', 'PRIVATE-counselling-CANARY', 'private@example.test', 'I am Asha', 'Jacaranda'] as $secret) {
                $this->assertStringNotContainsString($secret, $body);
            }

            return true;
        });
    }

    public function test_correction_requires_working_and_wrong_subject_or_answer_fields_are_quarantined(): void
    {
        $session = $this->openTutor();
        $this->outputChange = fn ($output) => array_replace($output, ['kind' => 'correction']);
        $blocked = $this->turn($session);
        $this->assertSame('quarantined', $blocked['state']);
        $this->assertNull($blocked['response']);
        $this->assertSame(160, AiUsage::firstOrFail()->used_units);
        $this->assertSame(1, AiQuarantineSnapshot::count());
        $this->outputChange = fn ($output) => $output + ['answer' => 'Full answer must not escape'];
        $blocked = $this->turn($session, working: 'I divided the equal pieces.');
        $this->assertSame('quarantined', $blocked['state']);
        $this->assertNull($blocked['response']);
        $this->outputChange = fn ($output) => array_replace($output, ['subject_token' => str_repeat('a', 64)]);
        $this->assertSame('quarantined', $this->turn($session)['state']);
    }

    public function test_submitted_working_gets_provisional_correction_without_official_marks(): void
    {
        $session = $this->openTutor();
        $official = DB::table('question_marks')->count();
        $turn = $this->turn($session, working: 'I divide one by two because the parts are equal.');
        $this->assertSame('completed', $turn['state']);
        $this->assertSame('correction', $turn['response']['kind']);
        $this->assertTrue($turn['provisional']);
        $this->assertSame($official, DB::table('question_marks')->count());
    }

    public function test_parent_teacher_other_school_and_unapproved_accounts_cannot_read_raw_chat(): void
    {
        $session = $this->openTutor();
        $this->turn($session);
        foreach (['farid', 'tara', 'gina', 'ben'] as $name) {
            try {
                app(TutorSessions::class)->read($this->actor($name), $session['id']);
                $this->fail('Raw chat must remain learner-owned.');
            } catch (HttpExceptionInterface $error) {
                $this->assertContains($error->getStatusCode(), [403, 404]);
            } catch (ModelNotFoundException) {
                $this->assertTrue(true);
            }
        }
        $actor = $this->actor('asha');
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => app(TutorSessions::class)->read($actor, $session['id'])), 404);
        LearnerApproval::where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $this->refuses(fn () => app(TutorSessions::class)->read($actor, $session['id']), 403);
    }

    public function test_source_and_teacher_note_revocation_refuse_cached_turn_replays(): void
    {
        $note = app(LearnerNotes::class)->create($this->actor(), $this->schoolA->ids['asha'], $this->subject, $this->year, 'tutor', 'A tutor-only scaffold.');
        $session = $this->openTutor();
        $operation = (string) Str::uuid();
        $this->turn($session, $operation);
        app(LearnerNotes::class)->update($this->actor(), $note['id'], 1, 'Changed tutor-only scaffold.');
        $this->refuses(fn () => $this->turn($session, $operation), 409);
        $this->content->update(['active' => false]);
        $this->refuses(fn () => $this->turn($session), 404);
        Http::assertSentCount(3);
    }

    public function test_allocation_or_guardian_withdrawal_during_moderation_prevents_inference(): void
    {
        app(LearnerNotes::class)->create($this->actor(), $this->schoolA->ids['asha'], $this->subject, $this->year, 'tutor', 'Current teacher guidance.');
        $session = $this->openTutor();
        $this->afterInput = function () {
            TeachingAllocation::query()->delete();
            AcademicLeadership::where('user_id', $this->actor()->id)->delete();
        };
        $result = $this->turn($session);
        $this->assertSame('failed', $result['state']);
        $this->assertSame('inference_unavailable', $result['failure_code']);
        $this->assertNull($result['response']);
        Http::assertSentCount(1);
        $this->assertSame(0, AiUsage::firstOrFail()->used_units);
    }

    public function test_private_crop_is_encrypted_operation_bound_and_sent_without_name_pixels(): void
    {
        $session = $this->openTutor();
        $operation = (string) Str::uuid();
        $photo = $this->photo($session, $operation);
        $this->assertSame($photo, $this->photo($session, $operation));
        $this->assertSame(1, TutorPhoto::count());
        $row = TutorPhoto::firstOrFail();
        $encrypted = Storage::disk(TutorPhotos::DISK)->get($row->path);
        $this->assertStringNotContainsString("\x89PNG", $encrypted);
        $pixels = imagecreatefromstring(Crypt::decryptString($encrypted));
        $red = 0;
        for ($y = 0; $y < imagesy($pixels); $y++) {
            for ($x = 0; $x < imagesx($pixels); $x++) {
                if (imagecolorat($pixels, $x, $y) === 0xFF0000) {
                    $red++;
                }
            }
        }
        $this->assertSame(0, $red);
        $this->refuses(fn () => $this->turn($session, photos: [$photo['id']]), 404);
        $turn = $this->turn($session, $operation, working: 'I drew equal parts.', photos: [$photo['id']]);
        $this->assertSame('completed', $turn['state']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/chat/completions') && is_array($request['messages'][1]['content']));
        $this->assertStringNotContainsString('PRIVATE-PERSON', json_encode(Http::recorded()->map(fn ($row) => $row[0]->data())->all()));
    }

    public function test_missing_crop_approval_changed_bytes_and_revoked_photo_never_reach_a_provider(): void
    {
        $session = $this->openTutor();
        $operation = (string) Str::uuid();
        try {
            $this->photo($session, $operation, false);
            $this->fail('Explicit crop approval required.');
        } catch (AiUnavailable $error) {
            $this->assertSame('vision_crop_approval_required', $error->reason);
        }
        $photo = $this->photo($session, $operation);
        $row = TutorPhoto::firstOrFail();
        Storage::disk(TutorPhotos::DISK)->put($row->path, Crypt::encryptString('tampered bytes'));
        $turn = $this->turn($session, $operation, photos: [$photo['id']]);
        $this->assertSame('failed', $turn['state']);
        $this->assertSame('vision_media_invalid', $turn['failure_code']);
        $this->assertSame(0, AiUsage::count());
        app(TutorPhotos::class)->revoke($this->actor('asha'), $session['id'], $photo['id']);
        $this->refuses(fn () => $this->turn($session, photos: [$photo['id']]), 404);
        Http::assertNothingSent();
    }

    public function test_local_disclosure_routes_to_private_safeguarding_even_when_external_inference_is_disabled(): void
    {
        config(['ai.inference.enabled' => false]);
        $session = $this->openTutor();
        $turn = $this->turn($session, text: 'I am being abused and need help.');
        $this->assertSame('quarantined', $turn['state']);
        $this->assertNull($turn['question']);
        $this->assertNull($turn['response']);
        $this->assertSame(TutorSessions::SUPPORT_MESSAGE, $turn['support_message']);
        $this->assertSame(0, AiUsage::firstOrFail()->used_units);
        $this->assertSame('local-safeguarding-guard', AiUsage::firstOrFail()->provider);
        $this->assertSame(1, AiQuarantineSnapshot::count());
        $summary = app(TutorQueries::class)->parentSummary($this->actor('farid'), $this->schoolA->ids['asha'], $this->subject);
        $this->assertSame(0, $summary['learning_steps']);
        $this->assertStringNotContainsString('abused', json_encode($summary));
        Http::assertNothingSent();
    }

    public function test_quarantine_retention_purges_derived_photo_and_messages_with_module_off_but_keeps_curriculum_and_ledger(): void
    {
        $session = $this->openTutor();
        $operation = (string) Str::uuid();
        $photo = $this->photo($session, $operation);
        $this->turn($session, $operation, text: 'I want to hurt myself.', photos: [$photo['id']]);
        $usage = AiUsage::firstOrFail();
        // A crash before foreign-key linkage must not leave derived private copies.
        TutorMessage::query()->update(['ai_usage_id' => null]);
        SchoolModule::where('module', 'tutor')->update(['enabled' => false]);
        $path = TutorPhoto::firstOrFail()->path;
        $this->travel(31)->days();
        app(AiQuarantine::class)->prune();
        $message = TutorMessage::firstOrFail();
        $this->assertSame('purged', $message->state);
        $this->assertNull($message->learner_text);
        $this->assertNull($message->context_text);
        $this->assertNull($message->response);
        $this->assertNotNull(TutorPhoto::firstOrFail()->purged_at);
        Storage::disk(TutorPhotos::DISK)->assertMissing($path);
        $this->assertNotNull($usage->fresh()->quarantine_purged_at);
        $this->assertSame(1, TutorSession::count());
        $this->assertTrue($this->content->fresh()->active);
    }

    public function test_unknown_dispatch_outcome_is_charged_and_exact_retry_never_duplicates_compute(): void
    {
        $session = $this->openTutor();
        $operation = (string) Str::uuid();
        $this->networkFails = true;
        $first = $this->turn($session, $operation);
        $this->assertSame('failed', $first['state']);
        $this->assertSame('provider_unavailable', $first['failure_code']);
        $this->assertGreaterThan(0, AiUsage::firstOrFail()->used_units);
        $this->assertSame($first, $this->turn($session, $operation));
        $this->assertSame(1, AiUsage::count());
        $this->assertSame(1, TutorMessage::count());
    }

    public function test_parent_summary_is_template_learning_metadata_with_no_raw_dialogue(): void
    {
        $session = $this->openTutor();
        $this->turn($session, text: 'PRIVATE-OWN-QUESTION about fractions.');
        $summary = app(TutorQueries::class)->parentSummary($this->actor('farid'), $this->schoolA->ids['asha'], $this->subject);
        $this->assertSame(1, $summary['learning_steps']);
        $this->assertSame(1, $summary['learning_sessions']);
        $this->assertFalse($summary['contains_raw_chat']);
        $this->assertStringNotContainsString('PRIVATE-OWN-QUESTION', json_encode($summary));
        $this->refuses(fn () => app(TutorQueries::class)->parentSummary($this->actor('gina'), $this->schoolA->ids['asha'], $this->subject), 404);
    }

    public function test_registered_policy_rejects_arbitrary_source_payloads_and_subject_changes(): void
    {
        $session = $this->openTutor();
        $turn = $this->turn($session);
        $this->refuses(fn () => app(TutorFeaturePolicy::class)->resolve($this->actor('asha'), Capability::Complete,
            new FeatureInferenceRequest('tutor', $turn['operation_id'], ['message_id' => $turn['id'], 'learner_id' => $this->schoolA->ids['ben']])), 422);
        $this->content->update(['subject_id' => $this->schoolA->ids['science']]);
        $this->refuses(fn () => $this->turn($session), 404);
    }

    public function test_another_approved_active_student_cannot_read_the_owners_session(): void
    {
        $session = $this->openTutor();
        $ben = $this->actor('ben');
        $ben->forceFill(['password_change_required' => false, 'status' => 1])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->actor('farid')->id],
            ['status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->actor('farid')->id,
            'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        $this->assertSame($this->schoolA->ids['ben'], (int) app(TutorAccess::class)->learner($ben)->id);
        $this->refuses(fn () => app(TutorSessions::class)->read($ben, $session['id']), 404);
        Http::assertNothingSent();
    }

    public function test_simultaneous_exact_turn_is_refused_until_the_first_attempt_completes(): void
    {
        $session = $this->openTutor();
        $operation = (string) Str::uuid();
        $this->afterInput = function () use ($session, $operation) {
            try {
                $this->turn($session, $operation);
                $this->fail('Pending turn must not dispatch twice.');
            } catch (AiUnavailable $error) {
                $this->assertSame('tutor_turn_in_progress', $error->reason);
            }
        };
        $this->assertSame('completed', $this->turn($session, $operation)['state']);
        $this->assertSame(1, AiUsage::count());
        $this->assertSame(1, TutorMessage::count());
        Http::assertSentCount(3);
    }

    public function test_crash_before_message_linkage_replays_the_completed_gateway_receipt_without_new_compute(): void
    {
        $session = $this->openTutor();
        $operation = (string) Str::uuid();
        $first = $this->turn($session, $operation);
        TutorMessage::whereKey($first['id'])->update(['state' => 'pending', 'response' => null, 'ai_usage_id' => null,
            'attempt_id' => (string) Str::uuid(), 'lease_until' => now('UTC')->subMinute()]);
        $this->assertSame($first, $this->turn($session, $operation));
        $this->assertSame(1, AiUsage::count());
        Http::assertSentCount(3);
    }

    public function test_guardian_withdrawal_during_moderation_erases_access_before_inference(): void
    {
        $session = $this->openTutor();
        $this->afterInput = fn () => LearnerApproval::where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $this->refuses(fn () => $this->turn($session), 403);
        Http::assertSentCount(1);
        $this->assertSame(0, AiUsage::firstOrFail()->used_units);
    }

    public function test_learner_budget_and_module_readonly_are_explicit_without_new_compute(): void
    {
        $session = $this->openTutor();
        config(['ai.inference.features.tutor.learner_daily_tokens' => 1]);
        $turn = $this->turn($session);
        $this->assertSame('failed', $turn['state']);
        $this->assertSame('learner_daily_cap', $turn['failure_code']);
        $this->assertNull($turn['response']);
        $this->assertSame(0, AiUsage::count());
        SchoolModule::where('module', 'tutor')->update(['read_only' => true]);
        $this->refuses(fn () => $this->turn($session), 403);
        Http::assertNothingSent();
    }

    private function flaggedCrop(): array
    {
        $session = $this->openTutor();
        $operation = (string) Str::uuid();
        $reference = $this->photo($session, $operation);
        $this->turn($session, $operation, text: 'I am being abused.', photos: [$reference['id']]);
        $usage = AiUsage::firstOrFail();
        unset($reference['operation_id']);
        $reviewer = User::create(['first_name' => 'Synthetic', 'last_name' => 'Safeguard', 'email' => 'tutor-reviewer@example.test',
            'password' => Hash::make('Synthetic2026'), 'status' => 1]);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $reviewer->id, 'role' => 'Safeguarding', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $reviewer->assignRole('Safeguarding');

        return [$reviewer, $usage, $reference];
    }

    public function test_quarantine_crop_reader_is_exact_private_and_independent_of_withdrawn_learner_module(): void
    {
        [$reviewer, $usage, $reference] = $this->flaggedCrop();
        SchoolModule::where('module', 'tutor')->update(['enabled' => false]);
        LearnerApproval::where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $flag = AiFlag::firstOrFail();
        $data = app(AiFlagReviews::class)->media($reviewer, $flag->id, $reference['id']);
        $this->assertSame('image/png', $data['mime']);
        $this->assertSame($reference['sha256'], hash('sha256', $data['bytes']));
        $this->assertSame(60, getimagesizefromstring($data['bytes'])[1]);
        $this->assertStringNotContainsString('PRIVATE-PERSON', $data['bytes']);
        $this->assertSame('quarantined', $usage->fresh()->state);
    }

    public function test_quarantine_crop_reader_refuses_non_safeguarding_other_school_and_unbound_reference(): void
    {
        [$reviewer, $usage, $reference] = $this->flaggedCrop();
        $reader = app(TutorQuarantineMedia::class);
        $this->refuses(fn () => $reader->read($this->actor(), $usage, $reference), 403);
        $this->refuses(fn () => $reader->read($this->actor('farid'), $usage, $reference), 403);
        $this->refuses(fn () => $reader->read($reviewer, $usage, array_replace($reference, ['id' => (string) Str::uuid()])), 404);
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => $reader->read($reviewer, $usage, $reference)), 404);
        PlatformStaffIdentity::create(['user_id' => $reviewer->id, 'active' => true]);
        $this->refuses(fn () => $reader->read($reviewer, $usage, $reference), 403);
    }

    public function test_quarantine_crop_reader_hides_expired_or_purged_approved_pixels(): void
    {
        [$reviewer, $usage, $reference] = $this->flaggedCrop();
        $reader = app(TutorQuarantineMedia::class);
        $this->travel(31)->days();
        $this->refuses(fn () => $reader->read($reviewer, $usage, $reference), 404);
        app(AiQuarantine::class)->prune();
        $this->refuses(fn () => $reader->read($reviewer, $usage->fresh(), $reference), 404);
        $this->assertNotNull(TutorPhoto::firstOrFail()->purged_at);
    }

    public function test_quarantine_crop_reader_rechecks_reviewer_membership_after_private_file_read(): void
    {
        [$reviewer, $usage, $reference] = $this->flaggedCrop();
        $disk = Storage::disk(TutorPhotos::DISK);
        $adapter = \Mockery::mock(FilesystemAdapter::class);
        $adapter->shouldReceive('get')->once()->andReturnUsing(function ($path) use ($disk, $reviewer) {
            SchoolMembership::where('user_id', $reviewer->id)->update(['status' => 'inactive']);

            return $disk->get($path);
        });
        Storage::shouldReceive('disk')->with(TutorPhotos::DISK)->andReturn($adapter);
        $this->refuses(fn () => app(TutorQuarantineMedia::class)->read($reviewer, $usage, $reference), 403);
    }
}
