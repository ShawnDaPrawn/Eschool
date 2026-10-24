<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Jobs\Communication\ProcessMessage;
use App\Jobs\Communication\RecoverMessages;
use App\Jobs\Platform\SendMail;
use App\Mail\Platform\NotificationMail;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSchool;
use App\Models\Communication\Audience;
use App\Models\Communication\Message;
use App\Models\Communication\MessageRead;
use App\Models\Communication\MessageRecipient;
use App\Models\Notification;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\Communication\MessagePolicy;
use App\Services\Academics\TeachingGroups;
use App\Services\Communication\CommunicationAudiences;
use App\Services\Communication\CommunicationRecipients;
use App\Services\Communication\CommunicationRules;
use App\Services\Communication\MessageDelivery;
use App\Services\Communication\MessageFlow;
use App\Services\Communication\MessageQueries;
use App\Services\ParentPolicyService;
use App\Services\People\EnrolmentService;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class MessageEngineTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $year;

    private int $grade;

    private int $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10)->setTime(9, 0));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        foreach (['asha', 'ben'] as $key) {
            app(EnrolmentService::class)->place($this->schoolA->ids[$key], $this->schoolA->ids['section_a'], $this->year, '2026-01-01', 'Synthetic message enrolment');
        }
        $g = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Message fractions group', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $this->group = (int) $g->id;
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->teacher()->id)->value('id'), 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic message author'], $this->group);
        foreach (['asha', 'ben'] as $key) {
            app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids[$key], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'mark_rule' => 'catch_up', 'reason' => 'Synthetic audience member'], $this->group);
        }
        Queue::fake();
    }

    private function teacher(): User
    {
        return User::findOrFail($this->schoolA->ids['tara_user']);
    }

    private function person(string $key): User
    {
        return User::findOrFail($this->schoolA->ids[$key.'_user']);
    }

    private function data(array $changes = []): array
    {
        return $changes + ['revision' => 0, 'operation_id' => (string) Str::uuid(), 'title' => 'Private school message', 'markdown' => "# Fractions\n\nPrivate family details.",
            'channels' => ['in_app'], 'scheduled_local' => null, 'audience' => ['kind' => 'group', 'session_year_id' => $this->year, 'target_id' => $this->group, 'parents' => true]];
    }

    private function draft(?User $actor = null, array $changes = []): Message
    {
        $r = app(MessageFlow::class)->save($actor ?? $this->teacher(), null, $this->data($changes));

        return Message::findOrFail($r['id']);
    }

    private function submit(Message $m, ?User $actor = null): Message
    {
        $actor ??= $this->teacher();
        $preview = app(MessageFlow::class)->preview($actor, (int) $m->id);
        app(MessageFlow::class)->submit($actor, (int) $m->id, ['operation_id' => (string) Str::uuid(), 'revision' => $m->revision, 'fingerprint' => $preview['fingerprint']]);

        return $m->refresh();
    }

    private function approved(?Message $m = null): Message
    {
        $m = $this->submit($m ?? $this->draft());
        $preview = app(MessageFlow::class)->preview($this->superAdmin(), (int) $m->id);
        app(MessageFlow::class)->decide($this->superAdmin(), (int) $m->id, ['operation_id' => (string) Str::uuid(), 'revision' => $m->revision, 'fingerprint' => $preview['fingerprint'], 'decision' => 'approve', 'reason' => 'Reviewed synthetic audience and message']);

        return $m->refresh();
    }

    private function denied(callable $fn, int $code): void
    {
        try {
            $fn();
            $this->fail('Expected refusal');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($code, $e->getStatusCode());
        } catch (ModelNotFoundException $e) {
            $this->assertSame(404, $code);
        }
    }

    private function headers(string $person = 'tara'): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$this->schoolA->tokens[$person], 'X-School-Id' => '1'];
    }

    public function test_teacher_audience_is_limited_to_current_allocations_and_guardians(): void
    {
        $scope = $this->data()['audience'];
        $actual = app(MessagePolicy::class)->recipients($this->teacher(), $scope, '2026-02-10');
        $this->assertEqualsCanonicalizing([$this->person('farid')->id, $this->person('mona')->id, $this->person('gina')->id], array_keys($actual));
        $this->denied(fn () => $this->draft(null, ['audience' => ['kind' => 'school', 'session_year_id' => $this->year, 'parents' => false]]), 403);
        $this->denied(fn () => app(MessagePolicy::class)->recipients($this->person('theo'), $scope, '2026-02-10'), 403);
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->denied(fn () => app(MessagePolicy::class)->recipients($this->teacher(), $scope, '2026-02-10'), 403);
    }

    public function test_ended_guardian_link_overrides_legacy_parent_id(): void
    {
        GuardianLink::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->person('farid')->id, 'status' => 'revoked', 'can_approve' => false]);
        $actual = app(MessagePolicy::class)->recipients($this->teacher(), $this->data()['audience'], '2026-02-10');
        $this->assertArrayNotHasKey($this->person('farid')->id, $actual);
        $this->assertArrayHasKey($this->person('mona')->id, $actual);
    }

    public function test_grade_heads_need_no_approval_and_cannot_address_other_grades(): void
    {
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $this->teacher()->id, 'session_year_id' => $this->year, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade head']);
        $role = Role::where('name', 'Teacher')->firstOrFail();
        app(CommunicationRules::class)->save($this->superAdmin(), (int) $role->id, ['revision' => 0, 'allow_school' => false, 'requires_approval' => true]);
        $rules = app(CommunicationRules::class)->effective($this->teacher());
        $this->assertTrue($rules['grade_head_exempt']);
        $this->assertFalse($rules['requires_approval']);
        $this->assertSame('approved', $this->submit($this->draft())->state);
        $other = (int) GradeLevel::where('code', '8')->value('id');
        $this->denied(fn () => app(MessagePolicy::class)->scope($this->teacher(), ['kind' => 'grade', 'target_id' => $other, 'session_year_id' => $this->year], '2026-02-10'), 403);
    }

    public function test_role_rule_requires_management_and_revision_and_can_allow_school_scope(): void
    {
        $role = Role::where('name', 'Teacher')->firstOrFail();
        $data = ['revision' => 0, 'allow_school' => true, 'requires_approval' => true];
        $this->denied(fn () => app(CommunicationRules::class)->save($this->teacher(), (int) $role->id, $data), 403);
        app(CommunicationRules::class)->save($this->superAdmin(), (int) $role->id, $data);
        $this->denied(fn () => app(CommunicationRules::class)->save($this->superAdmin(), (int) $role->id, $data), 409);
        $m = $this->draft(null, ['audience' => ['kind' => 'school', 'session_year_id' => $this->year, 'parents' => false]]);
        $this->assertSame('pending', $this->submit($m)->state);
    }

    public function test_custom_lists_cannot_smuggle_other_contacts_or_other_school_ids(): void
    {
        $input = ['revision' => 0, 'operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'name' => 'Private parent contacts', 'active' => true, 'user_ids' => [$this->person('farid')->id]];
        $r = app(CommunicationAudiences::class)->save($this->teacher(), null, $input);
        $this->assertStringNotContainsString('Private parent contacts', Audience::findOrFail($r['id'])->getRawOriginal('name'));
        $this->denied(fn () => app(CommunicationAudiences::class)->save($this->teacher(), null, array_replace($input, ['operation_id' => (string) Str::uuid(), 'user_ids' => [$this->person('cara')->id]])), 403);
        $this->denied(fn () => app(CommunicationAudiences::class)->save($this->teacher(), null, array_replace($input, ['operation_id' => (string) Str::uuid(), 'user_ids' => [$this->schoolB->ids['farid_user']]])), 403);
        $m = $this->draft(null, ['audience' => ['kind' => 'custom', 'session_year_id' => $this->year, 'target_id' => $r['id'], 'parents' => false]]);
        app(CommunicationAudiences::class)->save($this->teacher(), $r['id'], array_replace($input, ['revision' => 1, 'operation_id' => (string) Str::uuid(), 'user_ids' => [$this->person('mona')->id]]));
        $this->assertSame([$this->person('farid')->id], $m->refresh()->audience['user_ids']);
    }

    public function test_approval_is_separate_from_author_and_edits_invalidate_it(): void
    {
        $m = $this->submit($this->draft());
        $this->assertSame('pending', $m->state);
        $this->assertFalse(app(MessagePolicy::class)->canReview($this->teacher(), $m));
        $this->assertFalse(app(MessagePolicy::class)->canReview($this->person('theo'), $m));
        $this->assertTrue(app(MessagePolicy::class)->canReview($this->superAdmin(), $m));
        $r = app(MessageFlow::class)->save($this->teacher(), (int) $m->id, $this->data(['revision' => $m->revision]));
        $this->assertSame('draft', $r['state']);
        $this->assertNull($m->refresh()->submitted_at);
        $this->assertFalse(app(MessagePolicy::class)->canReview($this->superAdmin(), $m));
    }

    public function test_uncertain_retry_is_deduplicated_and_changed_body_rejected(): void
    {
        $input = $this->data();
        $first = app(MessageFlow::class)->save($this->teacher(), null, $input);
        $this->assertSame($first, app(MessageFlow::class)->save($this->teacher(), null, $input));
        $this->assertSame(1, Message::count());
        $this->denied(fn () => app(MessageFlow::class)->save($this->teacher(), null, array_replace($input, ['title' => 'Different'])), 409);
        $m = Message::findOrFail($first['id']);
        $m->update(['revision' => $m->revision + 1]);
        $this->denied(fn () => app(MessageFlow::class)->save($this->teacher(), (int) $m->id, $this->data(['revision' => 1])), 409);
    }

    public function test_private_body_is_encrypted_and_html_and_network_images_are_removed(): void
    {
        $m = $this->draft(null, ['markdown' => "Hello<script>private_script()</script>\n\n![badge](https://tracking.example/pixel)\n\n[bad](javascript:alert(1))"]);
        $this->assertStringNotContainsString('private_script', $m->markdown);
        $this->assertStringNotContainsString('tracking.example', $m->markdown);
        $this->assertStringNotContainsString('javascript:', $m->markdown);
        $this->assertStringNotContainsString('Hello', $m->getRawOriginal('markdown'));
        $this->assertStringNotContainsString('Private school message', $m->getRawOriginal('title'));
        $this->assertStringNotContainsString('group', $m->getRawOriginal('audience'));
    }

    public function test_scheduling_uses_school_timezone_and_does_not_send_early(): void
    {
        app(CurrentSchool::class)->school()->update(['timezone' => 'Africa/Johannesburg']);
        app(CurrentSchool::class)->set(1);
        $m = $this->approved($this->draft(null, ['scheduled_local' => '2026-02-11T10:30']));
        $this->assertSame('2026-02-11 08:30', $m->scheduled_at->format('Y-m-d H:i'));
        $this->assertSame(0, app(MessageDelivery::class)->process((int) $m->id));
        $this->assertSame(0, MessageRecipient::count());
        $this->travelTo(now()->setDate(2026, 2, 11)->setTime(8, 31));
        $this->assertGreaterThan(0, app(MessageDelivery::class)->process((int) $m->id));
        $this->assertSame('sent', $m->refresh()->state);
    }

    public function test_authority_is_rechecked_at_scheduled_publication(): void
    {
        $m = $this->approved();
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        app(MessageDelivery::class)->process((int) $m->id);
        $this->assertSame('blocked', $m->refresh()->state);
        $this->assertSame('authority_changed', $m->blocked_code);
        $this->assertSame(0, MessageRecipient::count());
    }

    public function test_delivery_freezes_recipients_filters_channels_and_deduplicates(): void
    {
        $m = $this->approved();
        $this->assertSame(3, app(MessageDelivery::class)->process((int) $m->id));
        $this->assertSame(0, app(MessageDelivery::class)->process((int) $m->id));
        $this->assertSame(3, MessageRecipient::count());
        $this->assertSame(['in_app'], DB::table('notification_dispatches')->where('notification_id', $m->refresh()->notification_id)->distinct()->pluck('channel')->all());
        $notice = Notification::findOrFail($m->notification_id);
        $this->assertStringNotContainsString('Private', $notice->title.$notice->message.json_encode($notice->delivery_payload));
        $this->assertSame(3, $m->recipient_count);
        $this->assertTrue(app(MessageDelivery::class)->allows($this->person('farid'), 'communication.message', $notice->delivery_payload));
        $this->assertFalse(app(MessageDelivery::class)->allows($this->person('cara'), 'communication.message', $notice->delivery_payload));
    }

    public function test_reader_is_own_snapshot_only_and_revocation_and_withdrawal_stop_transport(): void
    {
        $m = $this->approved();
        app(MessageDelivery::class)->process((int) $m->id);
        $m->refresh();
        $body = app(MessageQueries::class)->reader($this->person('farid'), (int) $m->id);
        $this->assertStringContainsString('Private family', $body['markdown']);
        $this->assertArrayNotHasKey('audience', $body);
        $this->denied(fn () => app(MessageQueries::class)->reader($this->person('cara'), (int) $m->id), 404);
        $notice = Notification::findOrFail($m->notification_id);
        GuardianLink::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->person('farid')->id, 'status' => 'revoked', 'can_approve' => false]);
        $this->denied(fn () => app(MessageQueries::class)->reader($this->person('farid'), (int) $m->id), 404);
        $this->assertFalse(app(MessageDelivery::class)->allows($this->person('farid'), 'communication.message', $notice->delivery_payload));
        app(MessageFlow::class)->withdraw($this->teacher(), (int) $m->id, ['revision' => $m->revision, 'operation_id' => (string) Str::uuid(), 'reason' => 'Retract synthetic message']);
        $this->denied(fn () => app(MessageQueries::class)->reader($this->person('mona'), (int) $m->id), 404);
        $this->assertFalse(app(MessageDelivery::class)->allows($this->person('mona'), 'communication.message', $notice->delivery_payload));
    }

    public function test_read_acknowledgement_is_idempotent_and_does_not_expose_private_recipient_stats(): void
    {
        $m = $this->approved();
        app(MessageDelivery::class)->process((int) $m->id);
        $first = app(MessageQueries::class)->read($this->person('farid'), (int) $m->id);
        $this->travel(2)->minutes();
        $this->assertSame($first, app(MessageQueries::class)->read($this->person('farid'), (int) $m->id));
        $this->assertSame(1, MessageRead::count());
        $this->assertSame(1, app(MessageQueries::class)->editor($this->teacher(), (int) $m->id)['read_count']);
        $this->assertArrayNotHasKey('read_count', app(MessageQueries::class)->reader($this->person('farid'), (int) $m->id));
    }

    public function test_old_review_alert_cannot_deliver_after_a_revision_or_decision(): void
    {
        $m = $this->submit($this->draft());
        app(MessageDelivery::class)->process((int) $m->id);
        $m->refresh();
        $notice = Notification::findOrFail($m->review_notification_id);
        $this->assertTrue(app(MessageDelivery::class)->allows($this->superAdmin(), 'communication.approval_requested', $notice->delivery_payload));
        app(MessageFlow::class)->save($this->teacher(), (int) $m->id, $this->data(['revision' => $m->revision]));
        $this->assertFalse(app(MessageDelivery::class)->allows($this->superAdmin(), 'communication.approval_requested', $notice->delivery_payload));
    }

    public function test_api_enforces_school_role_owner_and_private_cache_headers_before_validation(): void
    {
        $m = $this->draft();
        $this->getJson('/api/v2/communication/configuration', $this->headers())->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.staff_available', true);
        $this->getJson('/api/v2/communication/configuration', $this->headers('farid'))->assertOk()->assertJsonPath('data.staff_available', false);
        $this->postJson('/api/v2/communication/drafts', [], $this->headers('farid'))->assertForbidden();
        $this->putJson('/api/v2/communication/drafts/'.$m->id, [], $this->headers('theo'))->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v2/communication/drafts/'.$m->id, ['Authorization' => 'Bearer '.$this->schoolB->tokens['tara'], 'X-School-Id' => '2'])->assertNotFound()->assertDontSee('Private school message');
        $this->getJson('/api/v2/communication/messages/'.$m->id, $this->headers('farid'))->assertNotFound();
        $this->postJson('/api/v2/communication/drafts', $this->data(), $this->headers())->assertOk()->assertJsonPath('data.state', 'draft');
    }

    public function test_recovery_dispatches_due_work_and_ignores_future_or_completed_messages(): void
    {
        $due = $this->approved();
        $future = $this->approved($this->draft(null, ['scheduled_local' => '2026-02-12T10:30']));
        Queue::fake();
        app(RecoverMessages::class)->handle(app(CurrentSchool::class));
        Queue::assertPushed(ProcessMessage::class, fn ($job) => $job->schoolId === 1 && $job->messageId === (int) $due->id);
        Queue::assertNotPushed(ProcessMessage::class, fn ($job) => $job->messageId === (int) $future->id);
    }

    public function test_panel_supports_private_draft_preview_submit_and_preserves_conflicting_text(): void
    {
        $this->actingAs($this->teacher(), 'web')->get('/communication-centre')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('Edit private message')->assertSee('Message fractions group');
        $m = $this->draft();
        $this->get('/communication-centre?message_id='.$m->id.'&preview_message=1')->assertOk()->assertSee('Audience and delivery preview')->assertSee('Submit previewed message');
        $m->update(['revision' => $m->revision + 1]);
        $this->post('/communication-centre/drafts/'.$m->id.'/save', $this->data(['revision' => 1, 'markdown' => 'Local text to preserve']))->assertStatus(409)->assertSee('Local text to preserve')->assertSee('Current saved text');
        $this->actingAs($this->person('theo'), 'web')->get('/communication-centre?message_id='.$m->id)->assertNotFound();
    }

    public function test_publication_is_immutable_and_later_roster_changes_do_not_invent_recipients(): void
    {
        $m = $this->approved();
        app(MessageDelivery::class)->process((int) $m->id);
        $m->refresh();
        $this->denied(fn () => $m->update(['markdown' => 'A replacement body']), 409);
        $m->refresh();
        $this->denied(fn () => app(MessageFlow::class)->save($this->teacher(), (int) $m->id, $this->data(['revision' => $m->revision])), 409);
        $newGuardian = $this->person('theo');
        GuardianLink::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $newGuardian->id, 'status' => 'active', 'can_approve' => false]);
        $this->denied(fn () => app(MessageQueries::class)->reader($newGuardian, (int) $m->id), 404);
        $this->assertCount(0, app(MessageQueries::class)->inbox($newGuardian, 1)['items']);
        SchoolMembership::where('school_id', 1)->where('user_id', $this->person('farid')->id)->update(['status' => 'suspended']);
        $this->denied(fn () => app(MessageQueries::class)->reader($this->person('farid'), (int) $m->id), 403);
    }

    public function test_optional_channel_choices_still_control_message_delivery(): void
    {
        $parent = $this->person('farid');
        app(NotificationRules::class)->choose($parent, 'communication.message', 'in_app', false);
        $m = $this->approved();
        $this->assertSame(2, app(MessageDelivery::class)->process((int) $m->id));
        $this->assertFalse(MessageRecipient::where('message_id', $m->id)->where('user_id', $parent->id)->firstOrFail()->notification_queued);
        // Notifications are a preference; an eligible recipient can still read the inbox.
        $this->assertSame((int) $m->id, app(MessageQueries::class)->reader($parent, (int) $m->id)['id']);
    }

    public function test_queue_failure_rolls_back_publication_and_retry_has_one_recipient_claim(): void
    {
        $m = $this->approved();
        $original = app(Notifier::class);
        $mock = \Mockery::mock(Notifier::class)->makePartial();
        $mock->shouldReceive('makeNotice')->andReturnUsing(fn (...$args) => $original->makeNotice(...$args));
        $mock->shouldReceive('send')->once()->andThrow(new \RuntimeException('Synthetic queue outage'));
        $this->app->instance(Notifier::class, $mock);
        try {
            app(MessageDelivery::class)->process((int) $m->id);
            $this->fail('Expected queue outage');
        } catch (\RuntimeException $e) {
            $this->assertSame('Synthetic queue outage', $e->getMessage());
        }
        $this->assertSame('approved', $m->refresh()->state);
        $this->assertSame(0, MessageRecipient::count());
        $this->assertNull($m->publication_id);
        $this->app->instance(Notifier::class, $original);
        $this->assertSame(3, app(MessageDelivery::class)->process((int) $m->id));
        $this->assertSame(0, app(MessageDelivery::class)->process((int) $m->id));
        $this->assertSame(3, DB::table('notification_dispatches')->where('notification_id', $m->refresh()->notification_id)->count());
    }

    public function test_more_than_one_hundred_recipients_resume_from_a_durable_cursor(): void
    {
        $ids = [];
        for ($i = 0; $i < 103; $i++) {
            $u = User::forceCreate(['first_name' => 'Synthetic', 'last_name' => 'Recipient '.$i, 'email' => 'batch'.$i.'@example.test', 'password' => $this->teacher()->password, 'gender' => 'Female', 'mobile' => '0000000000', 'status' => 1]);
            SchoolMembership::create(['school_id' => 1, 'user_id' => $u->id, 'role' => 'Parent', 'status' => 'active', 'starts_on' => '2026-01-01']);
            $ids[] = (int) $u->id;
        }
        $list = app(CommunicationAudiences::class)->save($this->superAdmin(), null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'name' => 'Synthetic batch', 'active' => true, 'user_ids' => $ids]);
        $m = $this->draft($this->superAdmin(), ['audience' => ['kind' => 'custom', 'session_year_id' => $this->year, 'target_id' => $list['id'], 'parents' => false]]);
        $m = $this->submit($m, $this->superAdmin());
        $this->assertSame(100, app(MessageDelivery::class)->process((int) $m->id));
        $this->assertSame('dispatching', $m->refresh()->state);
        $this->assertSame(103, $m->recipient_count);
        $this->assertSame(100, MessageRecipient::whereNotNull('processed_at')->count());
        Queue::assertPushed(ProcessMessage::class, fn ($job) => $job->messageId === (int) $m->id && $job->afterCommit === false);
        $this->assertSame(3, app(MessageDelivery::class)->process((int) $m->id));
        $this->assertSame('sent', $m->refresh()->state);
        $this->assertSame(103, MessageRecipient::where('notification_queued', true)->count());
        $this->assertSame(0, app(MessageDelivery::class)->process((int) $m->id));
    }

    public function test_fresh_transport_guard_rejects_withdrawn_and_wrong_publication_notifications(): void
    {
        $m = $this->approved();
        app(MessageDelivery::class)->process((int) $m->id);
        $m->refresh();
        $parent = $this->person('farid');
        $notice = Notification::findOrFail($m->notification_id);
        $delivery = NotificationDelivery::create(['school_id' => 1, 'recipient_user_id' => $parent->id, 'recipient_hash' => DeliveryLog::hash($parent->email), 'channel' => 'email', 'type' => 'email.communication.message', 'delivery_key' => (string) Str::uuid(), 'status' => 'queued']);
        app(NotificationRules::class)->choose($parent, 'communication.message', 'email', true);
        $guard = app(DeliveryGuard::class);
        $this->assertTrue($guard->allows($delivery, 'communication.message', 'email', $parent->email, $notice->delivery_payload));
        $this->assertFalse($guard->allows($delivery, 'communication.message', 'email', $parent->email, array_replace($notice->delivery_payload, ['communication_publication_id' => (string) Str::uuid()])));
        $this->assertFalse($guard->allows($delivery, 'communication.message', 'email', $parent->email, []));
        app(MessageFlow::class)->withdraw($this->teacher(), (int) $m->id, ['operation_id' => (string) Str::uuid(), 'revision' => $m->revision, 'reason' => 'Synthetic transport withdrawal']);
        $this->assertFalse($guard->allows($delivery, 'communication.message', 'email', $parent->email, $notice->delivery_payload));
    }

    public function test_school_addressing_grant_does_not_expand_a_grade_heads_review_authority(): void
    {
        $other = (int) GradeLevel::where('code', '8')->value('id');
        ClassSchool::whereKey($this->schoolA->ids['class_c'])->update(['grade_level_id' => $other]);
        app(EnrolmentService::class)->place($this->schoolA->ids['cara'], $this->schoolA->ids['section_c'], $this->year, '2026-01-01', 'Synthetic other grade recipient');
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $this->teacher()->id, 'session_year_id' => $this->year, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade head']);
        $role = Role::where('name', 'Teacher')->firstOrFail();
        app(CommunicationRules::class)->save($this->superAdmin(), (int) $role->id, ['revision' => 0, 'allow_school' => true, 'requires_approval' => true]);
        $list = app(CommunicationAudiences::class)->save($this->superAdmin(), null, ['revision' => 0, 'operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'name' => 'Another grade contact', 'active' => true, 'user_ids' => [$this->person('cara')->id]]);
        $m = $this->draft($this->person('theo'), ['audience' => ['kind' => 'custom', 'session_year_id' => $this->year, 'target_id' => $list['id'], 'parents' => false]]);
        $m = $this->submit($m, $this->person('theo'));
        $this->assertSame('pending', $m->state);
        $this->assertFalse(app(MessagePolicy::class)->canReview($this->teacher(), $m));
        $this->assertTrue(app(MessagePolicy::class)->canReview($this->superAdmin(), $m));
    }

    public function test_stale_group_membership_cannot_add_custom_contacts_after_an_incompatible_move(): void
    {
        $other = (int) GradeLevel::where('code', '8')->value('id');
        ClassSchool::whereKey($this->schoolA->ids['class_c'])->update(['grade_level_id' => $other]);
        app(EnrolmentService::class)->place($this->schoolA->ids['asha'], $this->schoolA->ids['section_c'], $this->year, '2026-02-10', 'Synthetic midyear move');
        TeachingGroupMembership::where('learner_id', $this->schoolA->ids['asha'])->where('teaching_group_id', $this->group)->update(['ends_on' => '2026-12-31']);
        $allowed = app(CommunicationRecipients::class)->allowed($this->teacher(), $this->year, '2026-02-10');
        $this->assertArrayNotHasKey($this->person('asha')->id, $allowed);
        $this->assertArrayNotHasKey($this->person('farid')->id, $allowed);
        $this->assertArrayHasKey($this->person('ben')->id, $allowed);
    }

    public function test_mandatory_school_channels_override_an_authors_optional_channel_selection(): void
    {
        app(NotificationRules::class)->change($this->superAdmin(), 'parent', 'communication.message', 'in_app', 'mandatory');
        $m = $this->approved($this->draft(null, ['channels' => ['email']]));
        $this->assertSame(3, app(MessageDelivery::class)->process((int) $m->id));
        $this->assertSame(['in_app'], DB::table('notification_dispatches')->where('notification_id', $m->refresh()->notification_id)->distinct()->pluck('channel')->all());
    }

    public function test_suspended_school_pauses_work_without_losing_recipient_intent(): void
    {
        $m = $this->approved();
        School::whereKey(1)->update(['status' => 'suspended']);
        $this->assertSame(0, app(MessageDelivery::class)->process((int) $m->id));
        $this->assertSame('approved', $m->refresh()->state);
        $this->assertSame(0, MessageRecipient::count());
        School::whereKey(1)->update(['status' => 'active']);
        $this->assertSame(3, app(MessageDelivery::class)->process((int) $m->id));
    }

    public function test_retained_messages_prevent_destructive_migration_rollback(): void
    {
        $m = $this->draft();
        $migration = require database_path('migrations/2026_10_24_100000_create_communication_centre.php');
        try {
            $migration->down();
            $this->fail('Retained messages must prevent rollback');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Retained communication data', $e->getMessage());
        }
        $this->assertNotNull(Message::find($m->id));
        $this->assertTrue(Schema::hasTable('message_reads'));
    }

    public function test_published_reader_inbox_and_read_ack_match_the_typed_private_contract(): void
    {
        $m = $this->approved();
        app(MessageDelivery::class)->process((int) $m->id);
        $uri = '/api/v2/communication/messages/'.$m->id;
        $reader = $this->getJson($uri, $this->headers('farid'))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->assertSame((int) $this->person('farid')->id, $reader['reader_id']);
        $this->assertStringContainsString('Private family', $reader['markdown']);
        $this->assertArrayNotHasKey('audience', $reader);
        $this->assertArrayNotHasKey('read_count', $reader);
        $first = $this->postJson($uri.'/read', [], $this->headers('farid'))->assertOk()->json('data');
        $this->assertSame($first, $this->postJson($uri.'/read', [], $this->headers('farid'))->assertOk()->json('data'));
        $this->getJson('/api/v2/communication/messages', $this->headers('farid'))->assertOk()->assertJsonPath('data.items.0.id', (int) $m->id)->assertJsonPath('data.items.0.read_at', $first['read_at']);
        $this->getJson('/api/v2/communication/drafts/'.$m->id, $this->headers())->assertOk()->assertJsonPath('data.recipient_count', 3)->assertJsonPath('data.read_count', 1);
    }

    public function test_invalid_new_draft_preserves_composed_values_and_same_operation_without_global_old_input(): void
    {
        $input = $this->data(['title' => 'Retained local subject', 'markdown' => 'PRIVATE unsaved compose text', 'channels' => []]);
        $this->actingAs($this->teacher(), 'web');
        $response = $this->post('/communication-centre/drafts', $input)->assertRedirect()->assertSessionHasErrors('channels')->assertSessionMissing('_old_input');
        $this->assertStringNotContainsString('PRIVATE unsaved compose text', serialize(session()->all()));
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('PRIVATE unsaved compose text')->assertSee('Retained local subject')->assertSee('data-restored-draft', false)->assertSee('value="'.$input['operation_id'].'"', false);
        $this->assertSame(0, Message::count());
        $this->post('/communication-centre/drafts', array_replace($input, ['channels' => ['in_app']]))->assertRedirect();
        $this->assertStringContainsString('PRIVATE unsaved compose text', Message::sole()->markdown);
    }

    public function test_failed_edit_keeps_local_text_original_revision_and_an_explicitly_cleared_schedule(): void
    {
        $m = $this->draft(null, ['scheduled_local' => '2026-02-12T10:30']);
        $input = $this->data(['revision' => $m->revision, 'title' => 'Unsaved edit subject', 'markdown' => 'PRIVATE edited text', 'scheduled_local' => null, 'channels' => []]);
        $this->actingAs($this->teacher(), 'web');
        $response = $this->post('/communication-centre/drafts/'.$m->id.'/save', $input)->assertRedirect()->assertSessionHasErrors('channels');
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('PRIVATE edited text')->assertDontSee('value="2026-02-12T10:30"', false)->assertSee('data-restored-draft', false);
        $this->assertSame(1, $m->refresh()->revision);
        $this->assertStringNotContainsString('PRIVATE edited text', $m->markdown);
    }

    public function test_composed_recovery_is_never_reused_by_another_actor_school_or_draft(): void
    {
        $m = $this->draft();
        $this->actingAs($this->teacher(), 'web');
        $this->post('/communication-centre/drafts', $this->data(['markdown' => 'PRIVATE scoped recovery text', 'channels' => []]))->assertRedirect();
        $this->get('/communication-centre?message_id='.$m->id)->assertOk()->assertDontSee('PRIVATE scoped recovery text');
        $this->post('/communication-centre/drafts', $this->data(['markdown' => 'PRIVATE actor scoped text', 'channels' => []]))->assertRedirect();
        $this->actingAs($this->person('theo'), 'web')->get('/communication-centre')->assertOk()->assertDontSee('PRIVATE actor scoped text');
        $this->actingAs($this->superAdmin(), 'web');
        $this->post('/communication-centre/drafts', $this->data(['markdown' => 'PRIVATE school scoped text', 'channels' => []]))->assertRedirect();
        $admin = $this->superAdmin();
        app(CurrentSchool::class)->run(2, function () use ($admin) {
            $admin->unsetRelation('roles')->assignRole('Teacher');
            ParentPolicyService::acceptCurrent($admin);
        });
        $this->get('/communication-centre', ['X-School-Id' => '2'])->assertOk()->assertDontSee('PRIVATE school scoped text');
    }

    public function test_stale_audience_contacts_can_be_removed_and_an_empty_list_archived(): void
    {
        $farid = (int) $this->person('farid')->id;
        $mona = (int) $this->person('mona')->id;
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'name' => 'Parent list', 'active' => true, 'user_ids' => [$farid, $mona]];
        $list = app(CommunicationAudiences::class)->save($this->teacher(), null, $input);
        GuardianLink::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $farid, 'status' => 'revoked', 'can_approve' => false]);
        $this->actingAs($this->teacher(), 'web')->get('/communication-centre?audience_id='.$list['id'])->assertOk()->assertSee('Contacts no longer available in your scope')->assertDontSee('name="user_ids[]" value="'.$farid.'"', false);
        $this->post('/communication-centre/audiences/'.$list['id'], array_replace($input, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'user_ids' => [$mona]]))->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertSame([$mona], Audience::findOrFail($list['id'])->definition['user_ids']);
        GuardianLink::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $mona, 'status' => 'revoked', 'can_approve' => false]);
        $this->post('/communication-centre/audiences/'.$list['id'], array_replace($input, ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'active' => false, 'user_ids' => []]))->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertFalse(Audience::findOrFail($list['id'])->active);
        $this->assertSame([], Audience::findOrFail($list['id'])->definition['user_ids']);
    }

    public function test_review_notifications_open_the_private_named_workbench(): void
    {
        $m = $this->submit($this->draft());
        app(MessageDelivery::class)->process((int) $m->id);
        $notice = Notification::findOrFail($m->refresh()->review_notification_id);
        $this->assertSame(route('communication-centre.index', ['message_id' => $m->id], false), $notice->delivery_payload['link']);
        $this->actingAs($this->superAdmin(), 'web')->get($notice->delivery_payload['link'])->assertOk()->assertSee($m->title);
    }

    public function test_approval_email_uses_the_panel_host_when_the_family_portal_has_a_different_origin(): void
    {
        config(['app.url' => 'https://admin.example.test', 'identity.portal_url' => 'https://family.example.test']);
        app(NotificationRules::class)->choose($this->superAdmin(), 'communication.approval_requested', 'email', true);
        $m = $this->submit($this->draft());
        app(MessageDelivery::class)->process((int) $m->id);
        Queue::assertPushed(SendMail::class, fn ($job) => $job->mailable instanceof NotificationMail
            && $job->mailable->notificationType === 'communication.approval_requested'
            && $job->mailable->noticeUrl === 'https://admin.example.test'.route('communication-centre.index', ['message_id' => $m->id], false));
    }
}
