<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Jobs\Academics\NotifyContentReaders;
use App\Jobs\Academics\RecoverContentConversions;
use App\Models\Academics\AssessmentPlan;
use App\Models\Academics\ContentItem;
use App\Models\Academics\ContentRecipient;
use App\Models\Academics\ContentSource;
use App\Models\Academics\ContentVersion;
use App\Models\Academics\CurriculumPlan;
use App\Models\Academics\CurriculumTopic;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingAllocation;
use App\Models\Ai\AiFlag;
use App\Models\Ai\AiUsage;
use App\Models\ClassSchool;
use App\Models\Notification;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\Content\ContentFiles;
use App\Services\Academics\Content\ContentFlow;
use App\Services\Academics\Content\ContentNotices;
use App\Services\Academics\Content\ContentPipeline;
use App\Services\Academics\Content\ContentProcessingFailure;
use App\Services\Academics\Content\ContentQueries;
use App\Services\Academics\Content\ContentSettings;
use App\Services\Academics\TeachingGroups;
use App\Services\People\EnrolmentService;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class ContentEngineTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $grade;

    private int $year;

    private int $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        app(EnrolmentService::class)->place($this->schoolA->ids['asha'], $this->schoolA->ids['section_a'], $this->year, '2026-01-01', 'Synthetic content enrolment');
        $this->group = $this->group('Fractions group');
        app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids['asha'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'mark_rule' => 'catch_up', 'reason' => 'Synthetic content member'], $this->group);
        Storage::fake(ContentFiles::DISK);
        Http::preventStrayRequests();
        config(['academic_content.converter_url' => 'https://converter.example', 'academic_content.converter_token' => str_repeat('private-test-', 4), 'academic_content.source_origin' => 'https://backend.example']);
        $this->scanner();
        $this->converter();
    }

    private function teacher(): User
    {
        return User::findOrFail($this->schoolA->ids['tara_user']);
    }

    private function group(string $name): int
    {
        $g = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => $name, 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->teacher()->id)->value('id'), 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic content author'], $g->id);

        return (int) $g->id;
    }

    private function selection(array $changes = []): array
    {
        return $changes + ['subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'title' => 'Private fractions lesson', 'summary' => 'Read and practise.', 'group_ids' => [$this->group], 'topic_ids' => [], 'operation_id' => (string) Str::uuid()];
    }

    private function document(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('PRIVATE-source.md', "# Fractions\n\nOne half is two quarters.\n");
    }

    private function scanner(bool $clean = true, ?string $failure = null): void
    {
        $this->app->instance(UploadScanner::class, new class($clean, $failure) implements UploadScanner
        {
            public function __construct(private bool $clean, private ?string $failure) {}

            public function scan(string $bytes): array
            {
                if ($this->failure !== null) {
                    throw new \RuntimeException($this->failure);
                }

                return ['clean' => $this->clean, 'engine' => 'ClamAV synthetic current', 'signature' => $this->clean ? null : 'Eicar-Signature'];
            }
        });
    }

    private function converter(?callable $change = null, int $status = 200, string $markdown = "# Fractions\n\nOne half is two quarters."): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['https://converter.example/*' => function ($request) use ($change, $status, $markdown) {
            if ($change !== null) {
                $change($request);
            }

            return Http::response(['markdown' => $markdown, 'source_sha256' => $request['source_sha256'], 'generation_id' => $request['generation_id'], 'duration_seconds' => str_ends_with($request->url(), '/transcribe') ? 10 : 0, 'language' => str_ends_with($request->url(), '/transcribe') ? 'en' : null, 'review_required' => true], $status);
        }]);
    }

    private function create(): ContentVersion
    {
        $saved = app(ContentFlow::class)->create($this->teacher(), $this->selection(), $this->document());

        return ContentVersion::with('source', 'item')->findOrFail($saved['version_id']);
    }

    private function converted(): ContentVersion
    {
        $v = $this->create();
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);

        return $v->fresh(['source', 'item']);
    }

    private function write(ContentVersion $v, array $extra = []): array
    {
        return $extra + ['item_revision' => $v->item->revision, 'version_revision' => $v->revision, 'operation_id' => (string) Str::uuid()];
    }

    private function publish(ContentVersion $v): array
    {
        $preview = app(ContentFlow::class)->preview($this->teacher(), (int) $v->id);

        return app(ContentFlow::class)->publish($this->teacher(), (int) $v->id, $this->write($v, ['fingerprint' => $preview['fingerprint'], 'reviewed' => true]));
    }

    private function denied(int $code, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected refusal '.$code);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($code, $e->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $code);
        }
    }

    private function bearer(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($user->createToken('synthetic-content')->plainTextToken);
    }

    public function test_typed_api_upload_review_publish_read_and_idempotency_recheck_fresh_authority(): void
    {
        $this->bearer($this->teacher());
        $selection = $this->selection();
        $saved = $this->post('/api/v2/academics/content/items', ['selection' => json_encode($selection), 'source' => $this->document(), 'operation_id' => $selection['operation_id']], ['Accept' => 'application/json'])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $v = ContentVersion::with('item')->findOrFail($saved['version_id']);
        $this->getJson('/api/v2/academics/content/items/'.$saved['id'])->assertOk()->assertJsonPath('data.draft.state', 'queued')->assertJsonPath('data.published', null);
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
        $v = $v->fresh(['item']);
        $this->getJson('/api/v2/academics/content/configuration')->assertOk()->assertJsonCount(1, 'data.groups');
        $this->getJson('/api/v2/academics/content/items')->assertOk()->assertJsonCount(1, 'data.items');
        $this->getJson('/api/v2/academics/content/versions/'.$v->id)->assertOk()->assertJsonPath('data.source.scan_state', 'clean');
        $input = $this->write($v, ['selection' => $this->selection(), 'markdown' => "# Edited fractions\n\nHalf is two quarters."]);
        $this->putJson('/api/v2/academics/content/versions/'.$v->id, $input)->assertOk();
        $v = $v->fresh(['item']);
        $this->putJson('/api/v2/academics/content/versions/'.$v->id, array_replace($input, ['operation_id' => (string) Str::uuid()]))->assertConflict();
        $preview = $this->getJson('/api/v2/academics/content/versions/'.$v->id.'/preview')->assertOk()->json('data');
        $body = $this->write($v, ['fingerprint' => $preview['fingerprint'], 'reviewed' => true]);
        $publish = '/api/v2/academics/content/versions/'.$v->id.'/publish';
        $this->postJson($publish, $body)->assertOk();
        $this->postJson($publish, $body)->assertOk();
        $learner = $this->schoolA->ids['asha'];
        $this->bearer(User::findOrFail($this->schoolA->ids['farid_user']));
        $this->getJson('/api/v2/academics/content/learners/'.$learner)->assertOk()->assertJsonCount(1, 'data.items');
        $this->getJson('/api/v2/academics/content/learners/'.$learner.'/versions/'.$v->id)->assertOk()->assertJsonPath('data.title', 'Private fractions lesson')->assertDontSee('PRIVATE-source');
        $this->postJson('/api/v2/academics/content/learners/'.$learner.'/versions/'.$v->id.'/read')->assertOk()->assertJsonPath('data.read', true);
        $this->getJson('/api/v2/academics/content/learners/'.$this->schoolA->ids['ben'].'/versions/'.$v->id)->assertNotFound();
        $this->bearer($this->teacher());
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->postJson($publish, $body)->assertForbidden();
    }

    public function test_json_api_saves_an_allowed_markdown_draft_larger_than_one_mebibyte(): void
    {
        $v = $this->converted();
        $this->bearer($this->teacher());
        $body = "# Large reviewed lesson\n\n".str_repeat('Half "quoted" and two quarters. ', 36000);
        $this->assertGreaterThan(1024 * 1024, strlen($body));
        $this->assertLessThan(2 * 1024 * 1024, strlen($body));
        $input = $this->write($v, ['selection' => $this->selection(), 'markdown' => $body]);
        $this->putJson('/api/v2/academics/content/versions/'.$v->id, $input)->assertOk();
        $this->assertStringContainsString('Large reviewed lesson', $v->fresh()->markdown);
    }

    public function test_every_staff_content_route_rejects_wrong_profile_owner_and_school_before_validation(): void
    {
        $v = $this->converted();
        $private = [['GET', 'items/'.$v->content_item_id], ['POST', 'items/'.$v->content_item_id.'/source'], ['POST', 'items/'.$v->content_item_id.'/archive'], ['GET', 'versions/'.$v->id], ['PUT', 'versions/'.$v->id], ['GET', 'versions/'.$v->id.'/preview'], ['POST', 'versions/'.$v->id.'/retry'], ['POST', 'versions/'.$v->id.'/publish']];
        $this->bearer(User::findOrFail($this->schoolA->ids['farid_user']));
        foreach (array_merge($private, [['GET', 'configuration'], ['GET', 'items'], ['POST', 'items'], ['PUT', 'settings']]) as [$method,$path]) {
            $this->json($method, '/api/v2/academics/content/'.$path)->assertForbidden();
        }
        $this->bearer(User::findOrFail($this->schoolA->ids['theo_user']));
        foreach ($private as [$method,$path]) {
            $this->json($method, '/api/v2/academics/content/'.$path)->assertNotFound();
        }
        $this->bearer(app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['tara_user'])));
        foreach ($private as [$method,$path]) {
            $this->json($method, '/api/v2/academics/content/'.$path)->assertNotFound();
        }
        $this->bearer(User::findOrFail($this->schoolA->ids['farid_user']));
        foreach ([['GET', ''], ['GET', '/versions/'.$v->id], ['POST', '/versions/'.$v->id.'/read']] as [$method,$suffix]) {
            $this->json($method, '/api/v2/academics/content/learners/'.$this->schoolB->ids['asha'].$suffix)->assertNotFound();
        }
        $this->getJson('/api/v2/academics/content/versions/'.$v->id)->assertForbidden();
    }

    public function test_outbox_atomically_queues_only_identifiers_once_on_the_bounded_content_connection(): void
    {
        $v = $this->create();
        $event = OutboxEvent::where('type', 'academics.content_uploaded')->latest('id')->firstOrFail();
        $this->assertSame('dispatched', Outbox::dispatchOne((int) $event->id));
        $job = DB::table('jobs')->where('queue', 'content')->sole();
        $this->assertStringContainsString($v->generation_id, $job->payload);
        $this->assertStringNotContainsString('PRIVATE-source', $job->payload);
        $this->assertStringNotContainsString('fractions', $job->payload);
        $this->assertSame('skipped', Outbox::dispatchOne((int) $event->id));
        $this->assertSame(1, DB::table('jobs')->where('queue', 'content')->count());
        $this->assertSame(420, config('queue.connections.academic_content.retry_after'));
        $this->assertFalse(config('queue.connections.academic_content.after_commit'));
    }

    public function test_expired_attempts_are_settled_before_reclaim_and_recovery_cannot_overwrite_a_new_generation(): void
    {
        $v = $this->create();
        $attempt = (string) Str::uuid();
        $v->update(['state' => 'converting', 'attempt_id' => $attempt, 'lease_until' => now()->subMinute()]);
        $usage = AiUsage::create(['user_id' => $this->teacher()->id, 'request_hash' => str_repeat('a', 64), 'feature' => 'content_transcription', 'provider' => 'local-whisper-base', 'operation_id' => $attempt, 'usage_date' => now()->toDateString(), 'reserved_units' => 600, 'used_units' => 0, 'state' => 'reserved', 'cost_cents' => 0]);
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
        $this->assertSame('failed', $usage->fresh()->state);
        $this->assertSame(600, $usage->fresh()->used_units);
        $this->assertSame(0, $usage->fresh()->reserved_units);
        $this->assertSame('draft', $v->fresh()->state);
        $next = $this->create();
        $next->update(['state' => 'scanning', 'attempt_id' => (string) Str::uuid(), 'lease_until' => now()->subSecond()]);
        app(RecoverContentConversions::class)->handle(app(CurrentSchool::class), app(ContentPipeline::class));
        $this->assertSame('failed', $next->fresh()->state);
        $this->assertSame('content_worker_interrupted', $next->fresh()->failure_code);
        $next = $next->fresh(['item']);
        $generation = $next->generation_id;
        app(ContentFlow::class)->retry($this->teacher(), (int) $next->id, $this->write($next));
        app(ContentPipeline::class)->exhausted((int) $next->id, $generation);
        $this->assertSame('queued', $next->fresh()->state);
    }

    public function test_publication_notices_are_private_idempotent_and_rechecked_at_delivery(): void
    {
        Queue::fake();
        $v = $this->converted();
        $this->publish($v);
        $notices = app(ContentNotices::class);
        $this->assertGreaterThan(0, $notices->send((int) $v->id));
        $count = ContentRecipient::count();
        $this->assertSame(0, $notices->send((int) $v->id));
        $this->assertSame($count, ContentRecipient::count());
        $this->assertSame([$this->schoolA->ids['asha']], ContentRecipient::distinct()->pluck('learner_id')->all());
        $this->assertStringNotContainsString('fractions', Notification::where('type', 'academics.content_published')->firstOrFail()->message);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $parent->update(['fcm_id' => 'synthetic-private-content-token', 'device_type' => 'android']);
        $delivery = new NotificationDelivery(['school_id' => 1, 'recipient_user_id' => $parent->id, 'recipient_hash' => DeliveryLog::hash($parent->fcm_id)]);
        $guard = app(DeliveryGuard::class);
        $context = ['content_learner_id' => $this->schoolA->ids['asha'], 'content_version_id' => (int) $v->id];
        $this->assertTrue($guard->allows($delivery, 'academics.content_published', 'push', $parent->fcm_id, $context));
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $parent->id], ['relationship' => 'father', 'status' => 'ended']);
        $this->assertFalse($guard->allows($delivery, 'academics.content_published', 'push', $parent->fcm_id, $context));
        $this->assertFalse($guard->allows($delivery, 'academics.report_published', 'push', $parent->fcm_id, $context));
    }

    public function test_content_fanout_is_bounded_resumable_and_stops_after_completion(): void
    {
        Queue::fake();
        config(['academic_content.notification_batch_size' => 1]);
        app(EnrolmentService::class)->place($this->schoolA->ids['ben'], $this->schoolA->ids['section_a'], $this->year, '2026-01-01', 'Synthetic notice audience');
        app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids['ben'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'mark_rule' => 'catch_up', 'reason' => 'Synthetic second recipient'], $this->group);
        $v = $this->converted();
        $this->publish($v);
        $notices = app(ContentNotices::class);
        $notices->send((int) $v->id);
        $this->assertNull($v->item->fresh()->notice_completed_at);
        $this->assertSame($this->schoolA->ids['asha'], $v->item->fresh()->notice_after_learner_id);
        Queue::assertPushed(NotifyContentReaders::class, 1);
        $notices->send((int) $v->id);
        $this->assertNotNull($v->item->fresh()->notice_completed_at);
        $this->assertSame($this->schoolA->ids['ben'], $v->item->fresh()->notice_after_learner_id);
        $count = ContentRecipient::count();
        $this->assertSame(0, $notices->send((int) $v->id));
        $this->assertSame($count, ContentRecipient::count());
    }

    public function test_only_published_compatible_curriculum_topics_can_tag_content(): void
    {
        $plan = CurriculumPlan::create(['subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'version' => 1, 'title' => 'Synthetic curriculum', 'fingerprint' => str_repeat('a', 64), 'created_by' => $this->superAdmin()->id]);
        $topic = CurriculumTopic::create(['curriculum_plan_id' => $plan->id, 'term_id' => $this->schoolA->ids['term'], 'key' => (string) Str::uuid(), 'name' => 'Fractions']);
        $selection = $this->selection(['topic_ids' => [(int) $topic->id]]);
        $this->denied(404, fn () => app(ContentFlow::class)->create($this->teacher(), $selection, $this->document()));
        AssessmentPlan::create(['curriculum_plan_id' => $plan->id, 'term_id' => $this->schoolA->ids['term'], 'components' => [], 'state' => 'published', 'published_by' => $this->superAdmin()->id, 'published_at' => now()]);
        $saved = app(ContentFlow::class)->create($this->teacher(), $selection, $this->document());
        $this->assertSame([(int) $topic->id], ContentVersion::findOrFail($saved['version_id'])->metadata['topic_ids']);
        $this->assertSame((int) $topic->id, app(ContentQueries::class)->configuration($this->teacher(), $this->year)['topics'][0]['id']);
        $this->bearer($this->teacher());
        $this->getJson('/api/v2/academics/content/configuration')->assertOk()->assertJsonPath('data.topics.0.curriculum_version', 1);
    }

    public function test_native_panel_upload_edit_review_publish_and_owner_boundaries(): void
    {
        $this->actingAs($this->teacher(), 'web')->get('/academic-content')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('Upload private learning material')->assertSee('Fractions group');
        $selection = $this->selection();
        $create = $this->post('/academic-content/items', ['selection' => $selection, 'source' => $this->document(), 'operation_id' => $selection['operation_id']]);
        $create->assertRedirect();
        $v = ContentVersion::latest('id')->firstOrFail();
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
        $v = $v->fresh(['item']);
        $this->get('/academic-content?item_id='.$v->content_item_id)->assertOk()->assertSee('Markdown draft')->assertSee('One half is two quarters.')->assertDontSee('source_url');
        $this->post('/academic-content/versions/'.$v->id.'/save', $this->write($v, ['selection' => $selection, 'markdown' => "# Reviewed fractions\n\nOne half is two quarters."]))->assertRedirect();
        $this->post('/academic-content/versions/'.$v->id.'/save', $this->write($v, ['selection' => $selection, 'markdown' => "# Conflicting private text\n\nKeep my unsaved review."]))->assertConflict()->assertSee('Keep my unsaved review.')->assertSee('Current saved draft');
        $v = $v->fresh(['item']);
        $preview = app(ContentFlow::class)->preview($this->teacher(), (int) $v->id);
        $this->get('/academic-content?item_id='.$v->content_item_id.'&publication_preview=1')->assertOk()->assertSee('Review content publication')->assertSee('I reviewed the converted text');
        $this->post('/academic-content/versions/'.$v->id.'/publish', $this->write($v, ['fingerprint' => $preview['fingerprint'], 'reviewed' => '1']))->assertRedirect();
        $this->assertSame('published', $v->fresh()->state);
        $this->get('/academic-content?item_id='.$v->content_item_id)->assertOk()->assertSee('Private version history');
        $this->actingAs(User::findOrFail($this->schoolA->ids['theo_user']), 'web')->get('/academic-content?item_id='.$v->content_item_id)->assertNotFound();
        $this->flushSession();
        $this->actingAs(app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['tara_user'])), 'web')->get('/academic-content?item_id='.$v->content_item_id)->assertNotFound();
        $this->flushSession();
        $this->actingAs(User::findOrFail($this->schoolA->ids['farid_user']), 'web')->get('/academic-content')->assertRedirect(route('login'));
    }

    public function test_upload_conversion_and_retry_receipts_are_private_and_never_auto_publish(): void
    {
        $selection = $this->selection();
        $file = $this->document();
        $flow = app(ContentFlow::class);
        $one = $flow->create($this->teacher(), $selection, $file);
        $this->assertSame($one, $flow->create($this->teacher(), $selection, $file));
        $this->assertSame(1, ContentSource::count());
        $this->assertSame(1, ContentVersion::count());
        $v = ContentVersion::with('source')->findOrFail($one['version_id']);
        $this->assertStringNotContainsString('PRIVATE', DB::table('content_sources')->value('name'));
        $this->assertStringNotContainsString('Private fractions', DB::table('content_versions')->value('metadata'));
        $this->assertStringNotContainsString('# Fractions', Storage::disk(ContentFiles::DISK)->get($v->source->path));
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
        $v->refresh();
        $this->assertSame('draft', $v->state);
        $this->assertNull($v->item->published_version_id);
        $this->assertStringContainsString('# Fractions', $v->markdown);
        $this->assertStringNotContainsString('# Fractions', DB::table('content_versions')->value('markdown'));
        $this->assertSame($one, $flow->create($this->teacher(), $selection, $file));
        $this->denied(409, fn () => $flow->create($this->teacher(), array_replace($selection, ['title' => 'Different title']), $file));
        Http::assertSentCount(1);
    }

    public function test_infected_sources_are_quarantined_without_any_converter_or_publication(): void
    {
        $this->scanner(false);
        $v = $this->create();
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
        $this->assertSame('quarantined', $v->fresh()->state);
        $this->assertSame('infected', $v->source->fresh()->scan_state);
        $this->assertNull($v->fresh()->markdown);
        Http::assertNothingSent();
        $this->denied(409, fn () => app(ContentFlow::class)->preview($this->teacher(), (int) $v->id));
    }

    public function test_scanner_outage_is_actionable_and_stale_attempts_cannot_overwrite_retry(): void
    {
        $this->scanner(true, 'scanner_unavailable');
        $v = $this->create();
        try {
            app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
            $this->fail('Expected retryable outage');
        } catch (ContentProcessingFailure $e) {
            $this->assertSame('scanner_unavailable', $e->failureCode);
        }
        $this->assertSame('queued', $v->fresh()->state);
        app(ContentPipeline::class)->exhausted((int) $v->id, $v->generation_id);
        $v->refresh();
        $this->assertSame('failed', $v->state);
        $this->assertSame('scanner_unavailable', $v->failure_code);
        $old = $v->generation_id;
        app(ContentFlow::class)->retry($this->teacher(), (int) $v->id, $this->write($v->fresh(['item'])));
        $v->refresh();
        $this->assertNotSame($old, $v->generation_id);
        $this->scanner();
        app(ContentPipeline::class)->run((int) $v->id, $old);
        Http::assertNothingSent();
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
        $this->assertSame('draft', $v->fresh()->state);
    }

    public function test_publication_requires_review_and_current_group_scope_and_is_immutable(): void
    {
        $v = $this->converted();
        $preview = app(ContentFlow::class)->preview($this->teacher(), (int) $v->id);
        $body = $this->write($v, ['fingerprint' => $preview['fingerprint'], 'reviewed' => true]);
        $saved = app(ContentFlow::class)->publish($this->teacher(), (int) $v->id, $body);
        $this->assertSame($saved, app(ContentFlow::class)->publish($this->teacher(), (int) $v->id, $body));
        $this->assertSame('published', $v->fresh()->state);
        $this->denied(409, fn () => $v->fresh()->update(['markdown' => 'Rewrite released text']));
        $learner = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->assertSame((int) $v->id, (int) app(ContentPolicy::class)->reader($learner, $this->schoolA->ids['asha'], (int) $v->id)->id);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->assertSame((int) $v->id, (int) app(ContentPolicy::class)->reader($parent, $this->schoolA->ids['asha'], (int) $v->id)->id);
        $this->denied(404, fn () => app(ContentPolicy::class)->reader($parent, $this->schoolA->ids['ben'], (int) $v->id));
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->denied(403, fn () => app(ContentPolicy::class)->staff($this->teacher(), (int) $v->content_item_id, true));
        $this->assertSame((int) $v->id, (int) app(ContentPolicy::class)->reader($learner, $this->schoolA->ids['asha'], (int) $v->id)->id);
    }

    public function test_new_draft_preserves_published_body_and_audience_until_explicit_republication(): void
    {
        $v = $this->converted();
        $this->publish($v);
        $v = $v->fresh(['item', 'source']);
        $target = $this->group('Other fraction audience');
        $selection = $this->selection(['group_ids' => [$target]]);
        unset($selection['operation_id']);
        $draft = app(ContentFlow::class)->save($this->teacher(), (int) $v->id, $selection + $this->write($v, ['markdown' => "# Revised lesson\n\nRevised private body."]));
        $next = ContentVersion::with('item', 'source')->findOrFail($draft['version_id']);
        $this->assertNotSame((int) $v->id, (int) $next->id);
        $this->assertSame((int) $v->id, (int) $next->item->published_version_id);
        $this->assertStringContainsString('One half', $v->fresh()->markdown);
        $learner = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->assertSame((int) $v->id, (int) app(ContentPolicy::class)->reader($learner, $this->schoolA->ids['asha'], (int) $v->id)->id);
        $this->denied(404, fn () => app(ContentPolicy::class)->reader($learner, $this->schoolA->ids['asha'], (int) $next->id));
        $this->publish($next);
        $this->denied(404, fn () => app(ContentPolicy::class)->reader($learner, $this->schoolA->ids['asha'], (int) $v->id));
        $this->denied(404, fn () => app(ContentPolicy::class)->reader($learner, $this->schoolA->ids['asha'], (int) $next->id));
    }

    public function test_wrong_role_school_and_same_school_author_cannot_edit_private_content(): void
    {
        $v = $this->create();
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->denied(403, fn () => app(ContentPolicy::class)->staff($parent, (int) $v->content_item_id, true));
        $other = User::findOrFail($this->schoolA->ids['theo_user']);
        $this->denied(404, fn () => app(ContentPolicy::class)->staff($other, (int) $v->content_item_id, true));
        $this->denied(404, fn () => app(ContentFlow::class)->create($this->teacher(), $this->selection(['subject_id' => $this->schoolB->ids['maths']]), $this->document()));
        app(CurrentSchool::class)->run(2, fn () => $this->denied(404, fn () => app(ContentPolicy::class)->version(User::findOrFail($this->schoolB->ids['tara_user']), (int) $v->id, true)));
    }

    public function test_suspended_school_cannot_finish_or_read_a_private_conversion(): void
    {
        $this->converter(function () {
            School::whereKey(1)->update(['status' => 'suspended']);
        });
        $v = $this->create();
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
        $this->assertSame('failed', $v->fresh()->state);
        $this->assertNull($v->fresh()->markdown);
        $this->denied(404, fn () => app(ContentPolicy::class)->staff($this->teacher(), (int) $v->content_item_id));
    }

    public function test_revocation_during_conversion_never_saves_a_publishable_draft(): void
    {
        $this->converter(function () {
            TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        });
        $v = $this->create();
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
        $this->assertSame('failed', $v->fresh()->state);
        $this->assertNull($v->fresh()->markdown);
        $this->assertNull($v->item->published_version_id);
    }

    public function test_a_newer_source_supersedes_the_old_job_without_overwriting_its_draft(): void
    {
        $old = $this->create();
        $new = null;
        $this->converter(function () use ($old, &$new) {
            $v = $old->fresh(['item']);
            $selection = $this->selection();
            unset($selection['operation_id']);
            $saved = app(ContentFlow::class)->upload($this->teacher(), (int) $v->content_item_id, $selection + $this->write($v), UploadedFile::fake()->createWithContent('new.md', "# Replacement source\n\nNew file text."));
            $new = $saved['version_id'];
        });
        app(ContentPipeline::class)->run((int) $old->id, $old->generation_id);
        $this->assertSame('superseded', $old->fresh()->state);
        $this->assertNull($old->fresh()->markdown);
        $this->assertSame('queued', ContentVersion::findOrFail($new)->state);
        $this->assertNull(ContentVersion::findOrFail($new)->markdown);
        $this->assertSame((int) $new, (int) ContentItem::findOrFail($old->content_item_id)->draft_version_id);
    }

    private function audio(): UploadedFile
    {
        $encoded = file_get_contents(base_path('../services/converter/tests/fixtures/fractions.wav.gz.b64'));

        return UploadedFile::fake()->createWithContent('fractions.wav', gzdecode(base64_decode($encoded, true)));
    }

    public function test_transcription_uses_the_gateway_and_meters_actual_duration_with_a_small_school_allowance(): void
    {
        app(ContentSettings::class)->save($this->superAdmin(), ['transcription_enabled' => true, 'daily_transcription_seconds' => 600, 'revision' => 0]);
        $this->converter(function ($request) {
            $this->assertSame(600, $request['maximum_seconds']);
        });
        $saved = app(ContentFlow::class)->create($this->teacher(), $this->selection(), $this->audio());
        $v = ContentVersion::findOrFail($saved['version_id']);
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
        $usage = AiUsage::sole();
        $this->assertSame('completed', $usage->state);
        $this->assertSame(10, $usage->used_units);
        $this->assertSame(0, $usage->reserved_units);
        $this->assertSame(0, $usage->cost_cents);
        $this->assertSame('draft', $v->fresh()->state);
        $this->assertSame(10, $v->fresh()->duration_seconds);
    }

    public function test_known_preflight_refusal_releases_the_allowance_and_transcription_can_be_disabled(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['https://converter.example/*' => Http::response(['error' => 'converter_busy'], 503)]);
        $saved = app(ContentFlow::class)->create($this->teacher(), $this->selection(), $this->audio());
        $v = ContentVersion::findOrFail($saved['version_id']);
        try {
            app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
            $this->fail('Expected busy retry');
        } catch (ContentProcessingFailure $e) {
            $this->assertFalse($e->chargeReservation);
        }
        $this->assertSame(0, AiUsage::sole()->used_units);
        $this->assertSame(0, AiUsage::sole()->reserved_units);
        app(ContentSettings::class)->save($this->superAdmin(), ['transcription_enabled' => false, 'daily_transcription_seconds' => 600, 'revision' => 0]);
        $this->converter();
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
        $this->assertSame('transcription_disabled', $v->fresh()->failure_code);
        $this->assertSame('failed', $v->fresh()->state);
    }

    public function test_unsafe_transcription_is_flagged_without_storing_it_as_learner_content(): void
    {
        $this->converter(markdown: "# Transcript\nSend me nudes");
        $saved = app(ContentFlow::class)->create($this->teacher(), $this->selection(), $this->audio());
        $v = ContentVersion::findOrFail($saved['version_id']);
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
        $this->assertSame('quarantined', $v->fresh()->state);
        $this->assertNull($v->fresh()->markdown);
        $this->assertSame('safeguarding_review', AiFlag::sole()->code);
        $this->assertSame(10, AiUsage::sole()->used_units);
        $this->assertStringNotContainsString('nudes', json_encode(DB::table('audit_log')->get()));
    }

    public function test_source_capability_serves_only_current_scanned_generation_and_expires_after_conversion(): void
    {
        $uri = null;
        $sourceResponse = null;
        $tamperedResponse = null;
        $this->converter(function ($request) use (&$uri, &$sourceResponse, &$tamperedResponse) {
            $url = $request['source_url'];
            $uri = parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);
            $sourceResponse = app(CurrentSchool::class)->run(1, fn () => $this->get($uri));
            $tamperedResponse = app(CurrentSchool::class)->run(1, fn () => $this->get(str_replace('signature=', 'signature=0', $uri)));
        });
        $v = $this->converted();
        $sourceResponse->assertOk()->assertHeader('Content-Type', 'application/octet-stream')->assertContent($this->document()->getContent());
        $tamperedResponse->assertForbidden();
        $this->assertNotNull($uri);
        $this->get($uri)->assertNotFound();
        $wrong = URL::temporarySignedRoute('content-converter.source', now()->addMinutes(5), ['school' => 2, 'version' => $v->id, 'generation' => $v->generation_id], false);
        $this->get($wrong)->assertNotFound();
        $this->travel(6)->minutes();
        $this->get($uri)->assertForbidden();
        $this->assertSame(1, DB::table('audit_log')->where('action', 'academics.content_source_delivered')->count());
    }

    public function test_reader_and_staff_queries_expose_only_authorized_published_body_and_bound_offline_lease(): void
    {
        $v = $this->converted();
        $this->publish($v);
        $queries = app(ContentQueries::class);
        $configuration = $queries->configuration($this->teacher(), $this->year);
        $this->assertSame($this->group, $configuration['groups'][0]['id']);
        $this->assertSame(1, count($queries->staff($this->teacher(), $this->year)['items']));
        $this->assertSame((int) $v->id, $queries->show($this->teacher(), (int) $v->content_item_id)['published']['id']);
        $user = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->assertSame(1, count($queries->library($user, $this->schoolA->ids['asha'])['items']));
        $reader = $queries->reader($user, $this->schoolA->ids['asha'], (int) $v->id);
        $this->assertSame((int) $user->id, $reader['reader_id']);
        $this->assertSame(1, $reader['school_id']);
        $this->assertSame($this->schoolA->ids['asha'], $reader['learner_id']);
        $this->assertSame(hash('sha256', $reader['markdown']), $reader['body_sha256']);
        $this->assertSame(now()->addDay()->toISOString(), $reader['offline_until']);
        $this->assertArrayNotHasKey('source', $reader);
        $this->assertStringNotContainsString('PRIVATE-source', json_encode($reader));
        $queries->read($user, $this->schoolA->ids['asha'], (int) $v->id);
        $this->assertNotNull(ContentRecipient::sole()->read_at);
        app(ContentFlow::class)->archive($this->teacher(), (int) $v->content_item_id, ['item_revision' => $v->item->fresh()->revision, 'operation_id' => (string) Str::uuid(), 'reason' => 'Material withdrawn for review']);
        $this->assertSame([], $queries->library($user, $this->schoolA->ids['asha'])['items']);
        $this->denied(404, fn () => $queries->reader($user, $this->schoolA->ids['asha'], (int) $v->id));
    }

    public function test_unreviewed_publication_and_leadership_without_a_reason_are_refused(): void
    {
        $v = $this->converted();
        $preview = app(ContentFlow::class)->preview($this->teacher(), (int) $v->id);
        try {
            app(ContentFlow::class)->publish($this->teacher(), (int) $v->id, $this->write($v, ['fingerprint' => $preview['fingerprint'], 'reviewed' => false]));
            $this->fail('Unreviewed content published');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reviewed', $e->errors());
        }
        $head = User::findOrFail($this->schoolA->ids['theo_user']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $head->id, 'session_year_id' => $this->year, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic appointed grade head']);
        $preview = app(ContentFlow::class)->preview($head, (int) $v->id);
        $this->assertTrue($preview['requires_reason']);
        $body = $this->write($v, ['fingerprint' => $preview['fingerprint'], 'reviewed' => true]);
        $this->denied(422, fn () => app(ContentFlow::class)->publish($head, (int) $v->id, $body));
        $body['reason'] = 'Reviewed curriculum and correct audience';
        app(ContentFlow::class)->publish($head, (int) $v->id, $body);
        $this->assertSame((int) $head->id, (int) $v->fresh()->reviewed_by);
        $this->assertStringNotContainsString('Reviewed curriculum', DB::table('content_versions')->where('id', $v->id)->value('review_reason'));
    }

    public function test_unknown_inference_failure_remains_metered_and_cannot_bypass_the_daily_cap(): void
    {
        app(ContentSettings::class)->save($this->superAdmin(), ['transcription_enabled' => true, 'daily_transcription_seconds' => 600, 'revision' => 0]);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['https://converter.example/*' => Http::failedConnection()]);
        $saved = app(ContentFlow::class)->create($this->teacher(), $this->selection(), $this->audio());
        $v = ContentVersion::findOrFail($saved['version_id']);
        try {
            app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
            $this->fail('Expected uncertain outcome');
        } catch (ContentProcessingFailure $e) {
            $this->assertTrue($e->chargeReservation);
        }
        $this->assertSame(600, AiUsage::sole()->used_units);
        $this->assertSame(0, AiUsage::sole()->reserved_units);
        $this->converter();
        app(ContentPipeline::class)->run((int) $v->id, $v->generation_id);
        $this->assertSame('failed', $v->fresh()->state);
        $this->assertSame('transcription_daily_cap', $v->fresh()->failure_code);
        Http::assertNothingSent();
    }
}
