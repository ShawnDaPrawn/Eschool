<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Jobs\Academics\GenerateReportCard;
use App\Jobs\Platform\SendMail;
use App\Jobs\Platform\SendPush;
use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\MarkSnapshot;
use App\Models\Academics\ReportBatch;
use App\Models\Academics\ReportCard;
use App\Models\Academics\ReportComment;
use App\Models\Academics\ReportRecipient;
use App\Models\Academics\TeachingAllocation;
use App\Models\ClassSchool;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Policies\Academics\ReportPolicy;
use App\Services\Academics\CurriculumPlans;
use App\Services\Academics\MarkRecording;
use App\Services\Academics\Reports\ReportArtifacts;
use App\Services\Academics\Reports\ReportBatches;
use App\Services\Academics\Reports\ReportDesigns;
use App\Services\Academics\Reports\ReportFamily;
use App\Services\Academics\Reports\ReportNotices;
use App\Services\Academics\Reports\ReportQueries;
use App\Services\Academics\TeachingGroups;
use App\Services\Platform\Notifications\FcmClient;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class ReportEngineTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $group;

    private int $task;

    private int $term;

    private int $year;

    private int $grade;

    private array $questions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->term = $this->schoolA->ids['term'];
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Term::whereKey($this->term)->update(['start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic report fixture']);
        $plan = app(CurriculumPlans::class)->save($this->superAdmin(), ['subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year,
            'title' => 'Mathematics', 'annual_components' => ['sba' => 10000], 'rounding' => 'half_up', 'absent_policy' => 'incomplete', 'topics' => [],
            'assessments' => [['term_id' => $this->term, 'components' => ['sba' => 10000], 'tasks' => [['name' => 'Fractions', 'type' => 'test', 'component' => 'sba',
                'annual_component' => 'sba', 'annual_weight_bp' => 10000, 'weight_bp' => 10000, 'max_mark' => '20.00', 'due_on' => '2026-02-10', 'topic_keys' => [],
                'questions' => [['number' => '1', 'max_mark' => '7.50', 'topic_keys' => []], ['number' => '2', 'max_mark' => '12.50', 'topic_keys' => []]]]]]]]);
        $plan = app(CurriculumPlans::class)->publish($this->superAdmin(), $plan->id, $plan->assessments->sole()->id, 1);
        $this->task = $plan->assessments->sole()->tasks->sole()->id;
        $this->questions = $plan->assessments->sole()->tasks->sole()->questions->pluck('id')->all();
        $group = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Maths class', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $this->group = $group->id;
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->schoolA->ids['tara_user'])->value('id'),
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Allocated subject'], $this->group);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Enrolled subject'], $this->group);
    }

    private function teacher(): User
    {
        return User::findOrFail($this->schoolA->ids['tara_user']);
    }

    private function gradeHead(): User
    {
        $head = User::findOrFail($this->schoolA->ids['theo_user']);
        if (AcademicLeadership::where('user_id', $head->id)->where('kind', 'grade_head')->exists()) {
            return $head;
        }
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $head->id, 'session_year_id' => $this->year, 'kind' => 'grade_head', 'grade_level_id' => $this->grade,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Appointed grade head']);

        return $head;
    }

    private function input(?string $first = '7.50', ?string $second = '12.50', int $revision = 0): array
    {
        return ['rows' => [['learner_id' => $this->schoolA->ids['asha'], 'expected_revision' => $revision, 'disposition' => 'recorded', 'mark' => null,
            'question_marks' => [['question_id' => $this->questions[0], 'mark' => $first], ['question_id' => $this->questions[1], 'mark' => $second]]]]];
    }

    private function capture(array $input): array
    {
        return app(MarkRecording::class)->capture($this->teacher(), $this->group, $this->task, $input);
    }

    private function api(User $user): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($user->createToken('reports')->plainTextToken);
    }

    private function design(): array
    {
        return app(ReportDesigns::class)->save($this->superAdmin(), ['name' => 'Term report', 'design' => [
            'heading' => 'Term progress', 'introduction' => 'Progress & learning <script>bad()</script>', 'footer' => 'No fee restriction',
            'sections' => ['subjects', 'attendance', 'subject_comments', 'homeroom_comment'], 'show_crest' => false]]);
    }

    private function selection(): array
    {
        return ['template_id' => $this->design()['id'], 'session_year_id' => $this->year, 'term_id' => $this->term, 'class_section_id' => $this->schoolA->ids['section_a'],
            'learner_ids' => [$this->schoolA->ids['asha']], 'mode' => 'official'];
    }

    private function draft(): array
    {
        $this->capture($this->input('5.00', '10.00'));
        $selection = $this->selection();
        $service = app(ReportBatches::class);
        $preview = $service->preview($this->superAdmin(), $selection);
        $this->assertTrue($preview['can_prepare']);

        return $service->prepare($this->superAdmin(), $selection + ['fingerprint' => $preview['fingerprint']]);
    }

    private function principal(): User
    {
        $principal = $this->superAdmin();
        if (AcademicLeadership::where('user_id', $principal->id)->where('kind', 'principal')->exists()) {
            return $principal;
        }
        app(TeachingGroups::class)->dated($principal, 'leadership', ['user_id' => $principal->id, 'session_year_id' => $this->year, 'kind' => 'principal', 'grade_level_id' => null,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Report principal appointment']);

        return $principal;
    }

    private function sign(array $draft): array
    {
        $service = app(ReportBatches::class);
        $head = $service->approve($this->gradeHead(), $draft['id'], 'grade_head', $draft);

        return $service->approve($this->principal(), $draft['id'], 'principal', $head);
    }

    private function denied(int $status, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected refusal '.$status);
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        } catch (ModelNotFoundException $error) {
            $this->assertSame(404, $status);
        }
    }

    public function test_approved_report_generates_a_private_encrypted_pdf_and_releases_without_fee_checks(): void
    {
        Storage::fake(ReportArtifacts::DISK);
        $draft = $this->draft();
        $this->assertSame(1, $draft['revision']);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->denied(404, fn () => app(ReportPolicy::class)->family($parent, $draft['cards'][0]['id']));
        $approved = $this->sign($draft);
        $artifacts = app(ReportArtifacts::class);
        $cardId = $approved['cards'][0]['id'];
        $this->assertFalse(app(ReportBatches::class)->releasePreview($this->superAdmin(), $draft['id'])['can_release']);
        $artifacts->generate($cardId, $approved['fingerprint']);
        $card = ReportCard::with('batch')->findOrFail($cardId);
        $bytes = $artifacts->read($card);
        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertStringContainsString('/Type /Page', $bytes);
        $this->assertGreaterThan(1000, strlen($bytes));
        $stored = Storage::disk(ReportArtifacts::DISK)->get($card->pdf_path);
        $this->assertStringNotContainsString('%PDF-', $stored);
        $this->assertStringNotContainsString('Asha', DB::table('report_cards')->where('id', $cardId)->value('payload'));
        $path = $card->pdf_path;
        $artifacts->generate($cardId, $approved['fingerprint']);
        $this->assertSame($path, $card->fresh()->pdf_path);
        // The fixture has unpaid fee items. Report services must not consult them.
        $feeQueries = [];
        DB::listen(function ($query) use (&$feeQueries) {
            if (preg_match('/fees_|payment_transactions/i', $query->sql)) {
                $feeQueries[] = $query->sql;
            }
        });
        $preview = app(ReportBatches::class)->releasePreview($this->superAdmin(), $draft['id']);
        $this->assertTrue($preview['can_release']);
        $released = app(ReportBatches::class)->release($this->superAdmin(), $draft['id'], $preview);
        $this->assertSame('released', $released['state']);
        $this->assertSame([], $feeQueries);
        $family = app(ReportPolicy::class)->family($parent, $cardId);
        $this->assertSame(7500, $family->payload['subjects'][0]['percentage_bp']);
        $this->assertSame($bytes, $artifacts->read($family));
        $html = view('academics.reports.pdf', ['card' => $family->payload, 'design' => $family->batch->design, 'comments' => [], 'signatures' => $family->batch->signature_data])->render();
        $this->assertStringContainsString('75.00%', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('Baobab', $html);
        if (! is_dir(storage_path('framework/testing'))) {
            mkdir(storage_path('framework/testing'), recursive: true);
        }
        file_put_contents(storage_path('framework/testing/report-preview.pdf'), $bytes);
        $this->denied(409, fn () => app(ReportBatches::class)->refresh($this->superAdmin(), $draft['id'], $released));
    }

    public function test_report_signatures_are_ordered_fresh_and_stale_jobs_cannot_reuse_a_previous_approval_cycle(): void
    {
        Storage::fake(ReportArtifacts::DISK);
        $draft = $this->draft();
        $principal = $this->principal();
        $service = app(ReportBatches::class);
        $this->denied(409, fn () => $service->approve($principal, $draft['id'], 'principal', $draft));
        $this->denied(403, fn () => $service->approve($this->teacher(), $draft['id'], 'grade_head', $draft));
        $head = $service->approve($this->gradeHead(), $draft['id'], 'grade_head', $draft);
        $approved = $service->approve($principal, $draft['id'], 'principal', $head);
        $oldFingerprint = $approved['fingerprint'];
        $freshSource = $service->sourcePreview($this->superAdmin(), $draft['id']);
        $refreshed = $service->refresh($this->superAdmin(), $draft['id'], ['revision' => $approved['revision'], 'fingerprint' => $freshSource['fingerprint']]);
        $this->assertSame('draft', $refreshed['state']);
        $this->assertEquals((object) [], $refreshed['signatures']);
        $head = $service->approve($this->gradeHead(), $draft['id'], 'grade_head', $refreshed);
        $again = $service->approve($principal, $draft['id'], 'principal', $head);
        $this->assertNotSame($oldFingerprint, $again['fingerprint']);
        app(ReportArtifacts::class)->generate($draft['cards'][0]['id'], $oldFingerprint);
        $this->assertNull(ReportCard::firstOrFail()->generated_at);
        app(ReportArtifacts::class)->generate($draft['cards'][0]['id'], $again['fingerprint']);
        $this->assertNotNull(ReportCard::firstOrFail()->generated_at);
    }

    public function test_changed_marks_and_missing_artifacts_block_release_but_drafts_can_refresh_without_a_development_freeze(): void
    {
        Storage::fake(ReportArtifacts::DISK);
        $draft = $this->draft();
        $approved = $this->sign($draft);
        $cardId = $draft['cards'][0]['id'];
        app(ReportArtifacts::class)->generate($cardId, $approved['fingerprint']);
        $this->capture($this->input('6.00', '10.00', 1));
        $service = app(ReportBatches::class);
        $this->denied(409, fn () => $service->release($this->superAdmin(), $draft['id'], $approved));
        $source = $service->sourcePreview($this->superAdmin(), $draft['id']);
        $fresh = $service->refresh($this->superAdmin(), $draft['id'], ['revision' => $approved['revision'], 'fingerprint' => $source['fingerprint']]);
        $this->assertSame(8000, $fresh['cards'][0]['payload']['subjects'][0]['percentage_bp']);
        $this->assertFalse($fresh['cards'][0]['pdf_ready']);
        $new = $this->sign($fresh);
        app(ReportArtifacts::class)->generate($cardId, $new['fingerprint']);
        Storage::disk(ReportArtifacts::DISK)->delete(ReportCard::findOrFail($cardId)->pdf_path);
        $this->assertFalse($service->releasePreview($this->superAdmin(), $draft['id'])['can_release']);
        $service->retry($this->superAdmin(), $draft['id'], $new);
        app(ReportArtifacts::class)->generate($cardId, $new['fingerprint']);
        $this->assertTrue($service->releasePreview($this->superAdmin(), $draft['id'])['can_release']);
        Storage::disk(ReportArtifacts::DISK)->put(ReportCard::findOrFail($cardId)->pdf_path, 'damaged ciphertext');
        $this->assertFalse($service->releasePreview($this->superAdmin(), $draft['id'])['can_release']);
        app(ReportArtifacts::class)->generate($cardId, $new['fingerprint']);
        $this->assertTrue($service->releasePreview($this->superAdmin(), $draft['id'])['can_release']);
    }

    public function test_teacher_comment_scopes_cas_and_school_ownership_are_enforced_and_comment_text_is_encrypted(): void
    {
        $draft = $this->draft();
        $cardId = $draft['cards'][0]['id'];
        $service = app(ReportBatches::class);
        $comment = ['subject_id' => $this->schoolA->ids['maths'], 'text' => 'Private synthetic report comment', 'revision' => 0, 'batch_revision' => $draft['revision']];
        $this->denied(403, fn () => $service->comment(User::findOrFail($this->schoolA->ids['theo_user']), $cardId, $comment));
        $saved = $service->comment($this->teacher(), $cardId, $comment);
        $this->assertSame(1, $saved['revision']);
        $raw = DB::table('report_comments')->first()->text;
        $this->assertStringNotContainsString('Private synthetic', $raw);
        $this->assertSame('Private synthetic report comment', ReportComment::firstOrFail()->text);
        $this->assertStringNotContainsString('Private synthetic', (string) DB::table('audit_log')->where('action', 'academics.report_comment_saved')->value('after'));
        $this->denied(409, fn () => $service->comment($this->teacher(), $cardId, $comment));
        $this->denied(403, fn () => $service->comment($this->teacher(), $cardId, array_replace($comment, ['subject_id' => $this->schoolA->ids['science'], 'batch_revision' => $saved['batch_revision']])));
        $this->denied(403, fn () => $service->preview($this->teacher(), $draft['selection']));
        app(CurrentSchool::class)->run(2, function () use ($draft) {
            $this->denied(404, fn () => app(ReportPolicy::class)->batch(User::findOrFail($this->schoolB->ids['tara_user']), $draft['id']));
        });
    }

    public function test_report_api_previews_signs_generates_and_releases_only_private_requester_bound_downloads(): void
    {
        Storage::fake(ReportArtifacts::DISK);
        $this->capture($this->input('5.00', '10.00'));
        $selection = $this->selection();
        $base = '/api/v2/academics/reports';
        $this->api($this->superAdmin());
        $this->getJson($base.'/roster?session_year_id='.$this->year.'&class_section_id='.$selection['class_section_id'].'&term_id='.$this->term)->assertOk()->assertJsonPath('data.learners.0.id', $this->schoolA->ids['asha']);
        $this->getJson($base.'/configuration?session_year_id='.$this->year)->assertOk()->assertJsonPath('data.homerooms.0.grade_order', 7);
        $this->putJson($base.'/comment-banks', ['subject_id' => null, 'scope' => 'homeroom', 'label' => 'Progress', 'text' => 'Steady progress', 'active' => true])->assertOk();
        $preview = $this->postJson($base.'/preview', $selection)->assertOk()->assertJsonPath('data.cards.0.subjects.0.percentage_bp', 7500)->json('data');
        $key = (string) Str::uuid();
        $draft = $this->withHeader('Idempotency-Key', $key)->postJson($base.'/prepare', $selection + ['fingerprint' => $preview['fingerprint']])->assertOk()->assertJsonPath('data.state', 'draft')->json('data');
        $this->postJson($base.'/prepare', $selection + ['fingerprint' => $preview['fingerprint']])->assertOk()->assertJsonPath('data.id', $draft['id']);
        $this->assertSame(1, ReportBatch::count());
        $this->withHeader('Idempotency-Key', '');
        $this->api($this->teacher());
        $comments = $this->getJson($base.'/comments?session_year_id='.$this->year)->assertOk()->json('data.rows');
        $this->assertCount(1, $comments);
        $this->assertSame($this->schoolA->ids['maths'], $comments[0]['subject_id']);
        $this->assertArrayNotHasKey('subjects', $comments[0]);
        $this->assertArrayNotHasKey('percentage_bp', $comments[0]);
        $this->postJson($base.'/cards/'.$draft['cards'][0]['id'].'/comment', ['subject_id' => $this->schoolA->ids['maths'], 'text' => 'Steady improvement', 'revision' => 0, 'batch_revision' => $draft['revision']])->assertOk();
        $this->api($this->superAdmin());
        $draft = $this->getJson($base.'/batches/'.$draft['id'])->assertOk()->json('data');
        $this->assertSame('Steady improvement', $draft['cards'][0]['comments'][0]['text']);
        $this->assertNull($draft['cards'][0]['download_url']);
        $this->api($this->gradeHead());
        $head = $this->postJson($base.'/batches/'.$draft['id'].'/grade-sign', $draft)->assertOk()->json('data');
        $this->api($this->principal());
        $approved = $this->postJson($base.'/batches/'.$draft['id'].'/principal-sign', $head)->assertOk()->json('data');
        app(ReportArtifacts::class)->generate($draft['cards'][0]['id'], $approved['fingerprint']);
        $release = $this->getJson($base.'/batches/'.$draft['id'].'/release-preview')->assertOk()->assertJsonPath('data.can_release', true)->json('data');
        $this->postJson($base.'/batches/'.$draft['id'].'/release', $release)->assertOk()->assertJsonPath('data.state', 'released');
        $this->api(User::findOrFail($this->schoolA->ids['farid_user']));
        $reports = $this->getJson($base.'/learners/'.$this->schoolA->ids['asha'])->assertOk()->json('data.reports');
        $this->assertCount(1, $reports);
        $url = $reports[0]['download_url'];
        $this->getJson($base.'/cards/'.$reports[0]['id'].'/access')->assertOk()->assertJsonPath('data.learner_id', $this->schoolA->ids['asha']);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson($base.'/cards/'.$reports[0]['id'].'/read')->assertOk()->assertJsonStructure(['data' => ['read_at']]);
        $this->api(User::findOrFail($this->schoolA->ids['mona_user']));
        $this->get($url)->assertForbidden();
        $this->api(User::findOrFail($this->schoolA->ids['farid_user']));
        $this->get($url.'&tampered=1')->assertForbidden();
        $this->travel(11)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_report_prepare_cached_replay_and_downloads_recheck_live_school_role_and_guardian_rights(): void
    {
        Storage::fake(ReportArtifacts::DISK);
        $draft = $this->draft();
        $approved = $this->sign($draft);
        app(ReportArtifacts::class)->generate($draft['cards'][0]['id'], $approved['fingerprint']);
        $service = app(ReportBatches::class);
        $released = $service->release($this->superAdmin(), $draft['id'], $service->releasePreview($this->superAdmin(), $draft['id']));
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $url = app(ReportFamily::class)->list($parent, $this->schoolA->ids['asha'])['reports'][0]['download_url'];
        $this->api($parent)->get($url)->assertOk();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $parent->id], ['status' => 'ended', 'relationship' => 'father', 'can_approve' => false]);
        $this->get($url)->assertNotFound();
        $this->getJson('/api/v2/academics/reports/cards/'.$draft['cards'][0]['id'].'/access')->assertNotFound();
        $this->getJson('/api/v2/academics/reports/learners/'.$this->schoolA->ids['asha'])->assertNotFound();
        $this->api($this->superAdmin());
        $selection = $this->selection();
        $preview = $this->postJson('/api/v2/academics/reports/preview', $selection)->assertOk()->json('data');
        $key = (string) Str::uuid();
        $body = $selection + ['fingerprint' => $preview['fingerprint']];
        $this->withHeader('Idempotency-Key', $key)->postJson('/api/v2/academics/reports/prepare', $body)->assertOk();
        SchoolMembership::where('user_id', $this->superAdmin()->id)->update(['status' => 'inactive']);
        $this->postJson('/api/v2/academics/reports/prepare', $body)->assertForbidden();
    }

    public function test_release_notices_preserve_prior_reads_deduplicate_and_suppress_revoked_guardian_transports(): void
    {
        Queue::fake();
        Mail::fake();
        Storage::fake(ReportArtifacts::DISK);
        $types = config('notification_types');
        $types['academics.report_published']['channels']['email'] = 'default_on';
        config(['notification_types' => $types]);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $parent->forceFill(['email_verified_at' => now(), 'fcm_id' => 'synthetic-report-device', 'device_type' => 'android'])->save();
        $draft = $this->draft();
        $approved = $this->sign($draft);
        app(ReportArtifacts::class)->generate($draft['cards'][0]['id'], $approved['fingerprint']);
        $service = app(ReportBatches::class);
        $service->release($this->superAdmin(), $draft['id'], $service->releasePreview($this->superAdmin(), $draft['id']));
        $cardId = $draft['cards'][0]['id'];
        $family = app(ReportFamily::class);
        $family->read($parent, $cardId);
        $notices = app(ReportNotices::class);
        $this->assertGreaterThan(0, $notices->send($draft['id']));
        $recipient = ReportRecipient::where('user_id', $parent->id)->where('report_card_id', $cardId)->firstOrFail();
        $this->assertNotNull($recipient->read_at);
        $this->assertNotNull($recipient->notification_id);
        $claims = DB::table('notification_dispatches')->count();
        $this->assertSame(0, $notices->send($draft['id']));
        $this->assertSame($claims, DB::table('notification_dispatches')->count());
        $mail = Queue::pushed(SendMail::class)->filter(fn ($job) => ($job->mailable->to[0]['address'] ?? null) === $parent->email);
        $this->assertCount(1, $mail);
        $push = Queue::pushed(SendPush::class)->filter(fn ($job) => ($job->message['message']['token'] ?? null) === 'synthetic-report-device');
        $this->assertCount(1, $push);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $parent->id], ['status' => 'ended', 'relationship' => 'father', 'can_approve' => false]);
        foreach ($mail as $job) {
            $job->handle();
        }
        $fcm = $this->mock(FcmClient::class, fn ($mock) => $mock->shouldNotReceive('send'));
        foreach ($push as $job) {
            $job->handle($fcm);
        }
        Mail::assertNothingSent();
        $this->assertSame(2, NotificationDelivery::where('recipient_user_id', $parent->id)->where('type', 'like', '%academics.report_published')->where('status', 'suppressed')->count());
    }

    public function test_released_report_models_cannot_be_edited_or_moved_to_bypass_history_guards(): void
    {
        Storage::fake(ReportArtifacts::DISK);
        $draft = $this->draft();
        $approved = $this->sign($draft);
        $cardId = $draft['cards'][0]['id'];
        app(ReportArtifacts::class)->generate($cardId, $approved['fingerprint']);
        $service = app(ReportBatches::class);
        $service->release($this->superAdmin(), $draft['id'], $service->releasePreview($this->superAdmin(), $draft['id']));
        $selection = $draft['selection'];
        $preview = $service->preview($this->superAdmin(), $selection);
        $otherDraft = $service->prepare($this->superAdmin(), $selection + ['fingerprint' => $preview['fingerprint']]);
        $card = ReportCard::findOrFail($cardId);
        $this->denied(409, fn () => $card->update(['report_batch_id' => $otherDraft['id']]));
        $card = $card->fresh();
        $this->denied(409, fn () => $card->update(['payload' => ['tampered' => true]]));
        $this->denied(409, fn () => $card->fresh()->delete());
        $batch = ReportBatch::findOrFail($draft['id']);
        $this->denied(409, fn () => $batch->update(['state' => 'draft']));
        $this->assertSame('released', $batch->fresh()->state);
        $this->assertSame(7500, $card->fresh()->payload['subjects'][0]['percentage_bp']);
    }

    public function test_independent_draft_comment_scopes_progress_together_and_replay_rechecks_current_allocation(): void
    {
        $draft = $this->draft();
        $teacher = $this->teacher();
        app(TeachingGroups::class)->dated($this->superAdmin(), 'homerooms', ['teacher_id' => Teacher::where('user_id', $teacher->id)->value('id'), 'class_section_id' => $this->schoolA->ids['section_a'],
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Report homeroom appointment']);
        $base = '/api/v2/academics/reports/cards/'.$draft['cards'][0]['id'].'/comment';
        $body = ['subject_id' => $this->schoolA->ids['maths'], 'text' => 'Subject progress', 'revision' => 0, 'batch_revision' => $draft['revision']];
        $this->api($teacher)->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($base, $body)->assertOk();
        $service = app(ReportBatches::class);
        $homeroom = $service->comment($teacher, $draft['cards'][0]['id'], ['subject_id' => null, 'text' => 'Homeroom progress', 'revision' => 0, 'batch_revision' => $draft['revision']]);
        $this->assertSame(3, $homeroom['batch_revision']);
        $this->assertSame(2, ReportComment::count());
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->postJson($base, $body)->assertForbidden();
    }

    public function test_incomplete_results_require_an_explicit_provisional_report_and_remain_visibly_partial(): void
    {
        Storage::fake(ReportArtifacts::DISK);
        $tasks = [];
        foreach (['First task', 'Uncaptured task'] as $name) {
            $tasks[] = ['name' => $name, 'type' => 'test', 'component' => 'sba', 'annual_component' => 'sba', 'annual_weight_bp' => 5000,
                'weight_bp' => 5000, 'max_mark' => '20.00', 'due_on' => '2026-02-10', 'topic_keys' => [], 'questions' => []];
        }
        $plan = app(CurriculumPlans::class)->save($this->superAdmin(), ['subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year,
            'title' => 'Partial report plan', 'annual_components' => ['sba' => 10000], 'rounding' => 'half_up', 'absent_policy' => 'incomplete', 'topics' => [],
            'assessments' => [['term_id' => $this->term, 'components' => ['sba' => 10000], 'tasks' => $tasks]]]);
        $plan = app(CurriculumPlans::class)->publish($this->superAdmin(), $plan->id, $plan->assessments->sole()->id, 1);
        app(MarkRecording::class)->capture($this->teacher(), $this->group, $plan->assessments->sole()->tasks->first()->id, ['rows' => [['learner_id' => $this->schoolA->ids['asha'], 'expected_revision' => 0, 'disposition' => 'recorded', 'mark' => '15.00', 'question_marks' => []]]]);
        $selection = $this->selection();
        $service = app(ReportBatches::class);
        $official = $service->preview($this->superAdmin(), $selection);
        $this->assertFalse($official['can_prepare']);
        $this->assertNull($official['cards'][0]['subjects'][0]['percentage_bp']);
        $this->denied(409, fn () => $service->prepare($this->superAdmin(), $selection + ['fingerprint' => $official['fingerprint']]));
        $selection['mode'] = 'running';
        $preview = $service->preview($this->superAdmin(), $selection);
        $this->assertTrue($preview['can_prepare']);
        $this->assertSame(5000, $preview['cards'][0]['subjects'][0]['coverage_bp']);
        $draft = $service->prepare($this->superAdmin(), $selection + ['fingerprint' => $preview['fingerprint']]);
        $this->assertTrue($draft['cards'][0]['payload']['provisional']);
        $approved = $this->sign($draft);
        app(ReportArtifacts::class)->generate($draft['cards'][0]['id'], $approved['fingerprint']);
        $html = view('academics.reports.pdf', ['card' => $draft['cards'][0]['payload'], 'design' => $draft['design'], 'comments' => [], 'signatures' => []])->render();
        $this->assertStringContainsString('Provisional running report', $html);
        $this->assertStringContainsString('50.00%', $html);
        $this->assertStringContainsString('75.00%', $html);
    }

    public function test_left_or_disabled_learner_can_receive_a_guardian_report_without_account_activation(): void
    {
        Storage::fake(ReportArtifacts::DISK);
        $draft = $this->draft();
        User::whereKey($this->schoolA->ids['asha_user'])->update(['status' => 0]);
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['ends_on' => '2026-02-10']);
        $service = app(ReportBatches::class);
        $source = $service->sourcePreview($this->superAdmin(), $draft['id']);
        $fresh = $service->refresh($this->superAdmin(), $draft['id'], ['revision' => $draft['revision'], 'fingerprint' => $source['fingerprint']]);
        $approved = $this->sign($fresh);
        app(ReportArtifacts::class)->generate($draft['cards'][0]['id'], $approved['fingerprint']);
        $service->release($this->superAdmin(), $draft['id'], $service->releasePreview($this->superAdmin(), $draft['id']));
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $card = app(ReportPolicy::class)->family($parent, $draft['cards'][0]['id']);
        $this->assertSame(7500, $card->payload['subjects'][0]['percentage_bp']);
        $this->assertSame(0, (int) User::findOrFail($this->schoolA->ids['asha_user'])->status);
        $job = new GenerateReportCard(2, (int) $card->id, $approved['fingerprint']);
        app()->call([$job, 'handle']);
        $this->assertSame(1, count(Storage::disk(ReportArtifacts::DISK)->allFiles()));
    }

    public function test_current_report_leadership_can_correct_and_sign_previous_year_evidence_with_an_audited_reason(): void
    {
        Storage::fake(ReportArtifacts::DISK);
        $draft = $this->draft();
        $next = SessionYear::create(['name' => '2027', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'default' => 0]);
        $head = User::findOrFail($this->schoolA->ids['theo_user']);
        $principal = $this->superAdmin();
        foreach ([[$head, 'grade_head', $this->grade], [$principal, 'principal', null]] as [$user, $kind, $grade]) {
            app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $user->id, 'session_year_id' => $next->id, 'kind' => $kind, 'grade_level_id' => $grade,
                'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'reason' => 'Current report leadership']);
        }
        $this->travelTo(now()->setDate(2027, 1, 10));
        $body = ['subject_id' => $this->schoolA->ids['maths'], 'text' => 'Reviewed historical comment', 'revision' => 0, 'batch_revision' => $draft['revision']];
        $service = app(ReportBatches::class);
        $this->denied(403, fn () => $service->comment($this->teacher(), $draft['cards'][0]['id'], $body));
        try {
            $service->comment($head, $draft['cards'][0]['id'], $body);
            $this->fail('Correction reason required');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('reason', $error->errors());
        }
        $service->comment($head, $draft['cards'][0]['id'], $body + ['reason' => 'Historical correction after rollover']);
        $this->assertStringNotContainsString('Historical correction after rollover', (string) DB::table('audit_log')->where('action', 'academics.report_comment_saved')->value('after'));
        $fresh = $service->data(ReportBatch::findOrFail($draft['id']));
        $signed = $service->approve($head, $draft['id'], 'grade_head', $fresh);
        $approved = $service->approve($principal, $draft['id'], 'principal', $signed);
        app(ReportArtifacts::class)->generate($draft['cards'][0]['id'], $approved['fingerprint']);
        $released = $service->release($principal, $draft['id'], $service->releasePreview($principal, $draft['id']));
        $this->assertSame('released', $released['state']);
        $this->assertSame(7500, $released['cards'][0]['payload']['subjects'][0]['percentage_bp']);
    }

    public function test_report_history_pages_do_not_drop_earlier_releases_or_broaden_parent_access(): void
    {
        Storage::fake(ReportArtifacts::DISK);
        $draft = $this->draft();
        $approved = $this->sign($draft);
        app(ReportArtifacts::class)->generate($draft['cards'][0]['id'], $approved['fingerprint']);
        $service = app(ReportBatches::class);
        $service->release($this->superAdmin(), $draft['id'], $service->releasePreview($this->superAdmin(), $draft['id']));
        // Synthetic retained versions exercise history paging without 50 PDF renders.
        $batch = ReportBatch::findOrFail($draft['id'])->getAttributes();
        $card = ReportCard::findOrFail($draft['cards'][0]['id'])->getAttributes();
        unset($batch['id'], $card['id']);
        for ($i = 0; $i < 50; $i++) {
            $id = DB::table('report_batches')->insertGetId($batch);
            DB::table('report_cards')->insert(array_replace($card, ['report_batch_id' => $id]));
        }
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $family = app(ReportFamily::class);
        $first = $family->list($parent, $this->schoolA->ids['asha']);
        $last = $family->list($parent, $this->schoolA->ids['asha'], 2);
        $this->assertSame(2, $first['last_page']);
        $this->assertCount(50, $first['reports']);
        $this->assertCount(1, $last['reports']);
        $this->assertSame($draft['cards'][0]['id'], $last['reports'][0]['id']);
        $batches = app(ReportQueries::class)->batches($this->superAdmin(), $this->year, 2);
        $this->assertSame(2, $batches['last_page']);
        $this->assertCount(1, $batches['batches']);
        $this->denied(404, fn () => $family->list(User::findOrFail($this->schoolA->ids['gina_user']), $this->schoolA->ids['asha'], 2));
    }

    public function test_unexpected_grade_remap_cannot_expose_new_grade_evidence_to_old_report_leadership(): void
    {
        $draft = $this->draft();
        $head = $this->gradeHead();
        $otherGrade = (int) GradeLevel::where('code', '8')->value('id');
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $otherGrade]);
        $service = app(ReportBatches::class);
        $this->denied(409, fn () => $service->sourcePreview($head, $draft['id']));
        $this->denied(409, fn () => $service->refresh($head, $draft['id'], ['revision' => $draft['revision'], 'fingerprint' => $draft['source_fingerprint']]));
        $this->denied(409, fn () => $service->approve($head, $draft['id'], 'grade_head', $draft));
        $this->assertSame(0, MarkSnapshot::where('grade_level_id', $otherGrade)->count());
        $this->assertSame($this->grade, ReportCard::firstOrFail()->payload['grade_level_id']);
        $this->assertSame([$draft['id']], array_column(app(ReportQueries::class)->batches($head, $this->year)['batches'], 'id'));
        $newHead = $this->teacher();
        AcademicLeadership::create(['user_id' => $newHead->id, 'kind' => 'grade_head', 'grade_level_id' => $otherGrade, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'New grade head']);
        $this->assertSame([], app(ReportQueries::class)->batches($newHead, $this->year)['batches']);
        $this->denied(403, fn () => app(ReportQueries::class)->show($newHead, $draft['id']));
        AcademicLeadership::where('user_id', $head->id)->update(['ends_on' => '2026-02-09']);
        $this->assertSame([], app(ReportQueries::class)->batches($head, $this->year)['batches']);

    }

    public function test_report_retry_storage_is_encrypted_and_scoped_to_the_active_school(): void
    {
        $actor = $this->superAdmin();
        $input = ['name' => 'Private report heading', 'design' => ['heading' => 'Private academic design', 'introduction' => null, 'footer' => null,
            'sections' => ['subjects'], 'show_crest' => false]];
        $this->api($actor)->withHeader('Idempotency-Key', 'same-report-template-key');
        $first = $this->putJson('/api/v2/academics/reports/templates', $input)->assertOk();
        $this->putJson('/api/v2/academics/reports/templates', $input)->assertOk()->assertHeader('Idempotent-Replayed', 'true')->assertExactJson($first->json());
        $record = DB::table('idempotency_keys')->where('route', 'api.v2.academics.reports.templates')->first();
        $this->assertNotNull($record);
        $this->assertStringNotContainsString('Private academic design', $record->response_body);
        $this->assertStringNotContainsString('Private academic design', (string) $record->response_headers);
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () use ($actor): void {
            $actor->unsetRelation('roles');
            $actor->assignRole('Super Admin');
            SchoolMembership::updateOrCreate(['school_id' => 2, 'user_id' => $actor->id, 'role' => 'Super Admin'], ['status' => 'active', 'starts_on' => '2026-01-01']);
        });
        $this->api($actor)->withHeader('X-School-Id', '2')->withHeader('Idempotency-Key', 'same-report-template-key');
        $second = $this->putJson('/api/v2/academics/reports/templates', $input)->assertOk()->assertHeaderMissing('Idempotent-Replayed');
        $this->assertNotSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(2, DB::table('idempotency_keys')->where('route', 'api.v2.academics.reports.templates')->count());
    }

    public function test_explicit_provisional_report_can_explain_excused_or_ungraded_results_without_inventing_a_score(): void
    {
        Storage::fake(ReportArtifacts::DISK);
        $selection = $this->selection();
        $service = app(ReportBatches::class);
        $official = $service->preview($this->superAdmin(), $selection);
        $this->assertFalse($official['can_prepare']);
        $selection['mode'] = 'running';
        $ungraded = $service->preview($this->superAdmin(), $selection);
        $this->assertTrue($ungraded['can_prepare']);
        $this->assertSame('ungraded', $ungraded['cards'][0]['subjects'][0]['result_status']);
        $this->assertNull($ungraded['cards'][0]['subjects'][0]['percentage_bp']);
        app(MarkRecording::class)->capture($this->teacher(), $this->group, $this->task, ['rows' => [['learner_id' => $this->schoolA->ids['asha'], 'expected_revision' => 0, 'disposition' => 'excused', 'mark' => null, 'note' => 'Approved exemption for this task', 'question_marks' => []]]]);
        $preview = $service->preview($this->superAdmin(), $selection);
        $this->assertTrue($preview['can_prepare']);
        $this->assertSame('excused', $preview['cards'][0]['subjects'][0]['result_status']);
        $this->assertNull($preview['cards'][0]['subjects'][0]['percentage_bp']);
        $draft = $service->prepare($this->superAdmin(), $selection + ['fingerprint' => $preview['fingerprint']]);
        $approved = $this->sign($draft);
        app(ReportArtifacts::class)->generate($draft['cards'][0]['id'], $approved['fingerprint']);
        $this->assertTrue($service->releasePreview($this->superAdmin(), $draft['id'])['can_release']);
        $html = view('academics.reports.pdf', ['card' => $draft['cards'][0]['payload'], 'design' => $draft['design'], 'comments' => [], 'signatures' => []])->render();
        $this->assertStringContainsString('Excused', $html);
        $this->assertStringContainsString('Provisional running report', $html);
    }

    public function test_annual_roster_and_report_use_the_school_year_dates_without_a_term(): void
    {
        Storage::fake(ReportArtifacts::DISK);
        $this->capture($this->input('5.00', '10.00'));
        $selection = $this->selection();
        $selection['term_id'] = null;
        $roster = app(ReportQueries::class)->roster($this->superAdmin(), $this->year, $selection['class_section_id'], null);
        $this->assertSame([$this->schoolA->ids['asha']], array_column($roster['learners'], 'id'));
        $service = app(ReportBatches::class);
        $preview = $service->preview($this->superAdmin(), $selection);
        $this->assertTrue($preview['can_prepare']);
        $this->assertSame('2026-01-01', $preview['cards'][0]['starts_on']);
        $this->assertSame('2026-12-31', $preview['cards'][0]['ends_on']);
        $this->assertNull($preview['cards'][0]['term_id']);
        $this->assertSame(7500, $preview['cards'][0]['subjects'][0]['percentage_bp']);
        $draft = $service->prepare($this->superAdmin(), $selection + ['fingerprint' => $preview['fingerprint']]);
        $approved = $this->sign($draft);
        app(ReportArtifacts::class)->generate($draft['cards'][0]['id'], $approved['fingerprint']);
        $this->assertTrue($service->releasePreview($this->superAdmin(), $draft['id'])['can_release']);
    }
}
