<?php

declare(strict_types=1);

namespace Tests\Feature\Activities;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\Activities\Chat\GroupChatMessage;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Activities\Chat\GroupChatMessages;
use App\Services\Activities\Chat\GroupChatNotices;
use App\Services\Activities\Chat\GroupChatRules;
use App\Services\Activities\Chat\GroupChatThreads;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class GroupChatNoticesTest extends TestCase
{
    use UsesTwoSchools;

    private User $admin;

    private User $parent;

    private User $teacher;

    private int $learner;

    private ActivityGroup $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
        $this->travelTo('2026-02-10 12:00:00 UTC');
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        config(['modules.family_forms' => ['always_on' => true], 'modules.groups' => ['always_on' => true], 'modules.chat' => ['always_on' => true]]);
        $this->admin = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        $this->teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->learner = $this->schoolA->ids['asha'];
        $year = $this->schoolA->ids['session_year'];
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        GuardianLink::updateOrCreate(['learner_id' => $this->learner, 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        app(ApprovalService::class)->decide($this->parent, Students::findOrFail($this->learner), 'approve', config('identity.consent_version'));
        $this->group = ActivityGroup::create(['session_year_id' => $year, 'name' => 'Synthetic consent group', 'discipline' => 'Culture', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
        ActivityStaff::create(['activity_group_id' => $this->group->id, 'user_id' => $this->teacher->id, 'role' => 'facilitator', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic allocation', 'revision' => 1]);
        ActivityMembership::create(['activity_group_id' => $this->group->id, 'learner_id' => $this->learner, 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic group', 'revision' => 1]);
    }

    private function create(array $input = [], ?User $actor = null): array
    {
        return app(GroupChatThreads::class)->create($actor ?? $this->teacher, (int) $this->group->id, $input + [
            'operation_id' => (string) Str::uuid(), 'persona' => 'staff', 'kind' => 'announcement', 'audience' => 'guardians',
            'title' => 'PRIVATE synthetic conversation title', 'body' => 'PRIVATE synthetic message body', 'language' => 'en']);
    }

    private function dispatch(array $message): void
    {
        $event = OutboxEvent::where('type', 'group_chat.message_posted')->where('payload->message_id', $message['message_id'])->sole();
        $this->assertSame('dispatched', Outbox::dispatchOne((int) $event->id));
    }

    private function notice(User $actor, string $persona): Notification
    {
        return Notification::where('source_key', 'like', 'group-chat:%:'.$actor->id.':'.$persona)->latest('id')->firstOrFail();
    }

    private function visible(User $actor)
    {
        return NotificationVisibility::forUser(Notification::where('type', 'like', 'group_chat.%'), (int) $actor->id, 4);
    }

    private function link(User $actor, Notification $notice): void
    {
        UserNotification::firstOrCreate(['user_id' => $actor->id, 'notification_id' => $notice->id]);
    }

    public function test_outbox_routes_generic_parent_notice_once_and_no_private_text_enters_delivery(): void
    {
        $message = $this->create();
        $event = OutboxEvent::where('type', 'group_chat.message_posted')->sole();
        $this->assertSame(['group_id', 'thread_id', 'message_id', 'revision'], array_keys($event->payload));
        $this->dispatch($message);
        $notice = $this->notice($this->parent, 'parent');
        $count = Notification::count();
        $fanouts = OutboxEvent::where('type', 'notification.requested')->count();
        app(GroupChatNotices::class)->handle($event);
        $this->assertSame($count, Notification::count());
        $this->assertSame($fanouts, OutboxEvent::where('type', 'notification.requested')->count());
        $this->assertSame('/activities/groups', $notice->delivery_payload['link']);
        $this->assertSame('Group conversation updated', $notice->title);
        $this->assertStringNotContainsString('PRIVATE', json_encode($notice->getAttributes()));
        $this->assertTrue(app(GroupChatNotices::class)->allows($this->parent, $notice->type, $notice->delivery_payload));
        $this->assertFalse(app(GroupChatNotices::class)->allows($this->teacher, $notice->type, $notice->delivery_payload));
        $this->assertContains('in_app', app(Notifier::class)->send($this->parent, $notice->type, $notice->title, $notice->message, null, $notice));
        $this->assertSame(1, $this->visible($this->parent)->count());
        $this->assertSame(0, $this->visible($this->teacher)->count());
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => $this->visible($this->parent)->count()));
    }

    public function test_private_reply_notifies_only_actual_group_staff_and_private_response_only_its_author(): void
    {
        $thread = $this->create();
        $reply = app(GroupChatMessages::class)->send($this->parent, (int) $this->group->id, $thread['thread_id'], [
            'operation_id' => (string) Str::uuid(), 'persona' => 'parent', 'visibility' => 'staff', 'body' => 'PRIVATE parent reply', 'language' => 'en']);
        $this->dispatch($reply);
        $staff = $this->notice($this->teacher, 'staff');
        $this->assertSame('/group-conversations', $staff->delivery_payload['link']);
        $this->assertSame(1, Notification::where('type', GroupChatNotices::STAFF)->count());
        $this->assertFalse(app(GroupChatNotices::class)->allows($this->admin, $staff->type, $staff->delivery_payload));
        $mother = User::findOrFail($this->schoolA->ids['mona_user']);
        $answer = app(GroupChatMessages::class)->send($this->teacher, (int) $this->group->id, $thread['thread_id'], [
            'operation_id' => (string) Str::uuid(), 'persona' => 'staff', 'visibility' => 'thread', 'reply_to' => $reply['message_id'], 'body' => 'PRIVATE answer', 'language' => 'en']);
        $this->dispatch($answer);
        $parent = $this->notice($this->parent, 'parent');
        $this->assertTrue(app(GroupChatNotices::class)->allows($this->parent, $parent->type, $parent->delivery_payload));
        $this->assertFalse(app(GroupChatNotices::class)->allows($mother, $parent->type, $parent->delivery_payload));
        $this->assertSame(1, Notification::where('type', GroupChatNotices::FAMILY)->count());
        $this->link($this->teacher, $staff);
        $this->assertSame(1, $this->visible($this->teacher)->count());
        ActivityStaff::query()->update(['active' => false]);
        $this->assertSame(0, $this->visible($this->teacher)->count());
        $this->assertFalse(app(GroupChatNotices::class)->allows($this->teacher, $staff->type, $staff->delivery_payload));
    }

    public function test_prepared_parent_inbox_rechecks_guardian_and_learner_membership_at_execution(): void
    {
        $this->dispatch($this->create());
        $notice = $this->notice($this->parent, 'parent');
        $this->link($this->parent, $notice);
        $query = $this->visible($this->parent);
        $this->assertSame(1, (clone $query)->count());
        GuardianLink::where('learner_id', $this->learner)->where('guardian_user_id', $this->parent->id)->update(['status' => 'inactive']);
        $this->assertSame(0, (clone $query)->count());
        $this->assertFalse(app(GroupChatNotices::class)->allows($this->parent, $notice->type, $notice->delivery_payload));
        GuardianLink::query()->update(['status' => 'active']);
        $student = Students::findOrFail($this->learner);
        SchoolMembership::where('user_id', $student->user_id)->update(['status' => 'ended']);
        $this->assertSame(0, (clone $query)->count());
        $this->assertFalse(app(GroupChatNotices::class)->allows($this->parent, $notice->type, $notice->delivery_payload));
    }

    public function test_role_module_platform_and_removal_withdraw_both_delivery_and_existing_inbox(): void
    {
        $message = $this->create();
        $this->dispatch($message);
        $notice = $this->notice($this->parent, 'parent');
        $this->link($this->parent, $notice);
        $query = $this->visible($this->parent);
        PlatformStaffIdentity::create(['user_id' => $this->parent->id, 'active' => true]);
        $this->assertSame(0, (clone $query)->count());
        $this->assertFalse(app(GroupChatNotices::class)->allows($this->parent, $notice->type, $notice->delivery_payload));
        PlatformStaffIdentity::query()->delete();
        $this->parent->syncRoles(['Facilitator']);
        $this->assertSame(0, (clone $query)->count());
        $this->parent->syncRoles(['Parent']);
        $this->assertSame(1, (clone $query)->count());
        app(GroupChatMessages::class)->remove($this->teacher, (int) $this->group->id, $message['thread_id'], $message['message_id'], [
            'operation_id' => (string) Str::uuid(), 'persona' => 'staff', 'revision' => 1, 'reason' => 'PRIVATE removal reason']);
        $this->assertSame(0, (clone $query)->count());
        $this->assertFalse(app(GroupChatNotices::class)->allows($this->parent, $notice->type, $notice->delivery_payload));
        config(['modules.chat' => ['default' => false, 'legacy' => false]]);
        $this->assertSame(0, (clone $query)->count());
    }

    public function test_rollback_and_expired_or_unassigned_source_never_fan_out(): void
    {
        $count = OutboxEvent::count();
        DB::beginTransaction();
        $this->create();
        DB::rollBack();
        $this->assertSame($count, OutboxEvent::count());
        $message = $this->create();
        GroupChatMessage::whereKey($message['message_id'])->update(['sent_at' => now()->utc()->subDays(31)]);
        $this->dispatch($message);
        $this->assertSame(0, Notification::where('type', 'like', 'group_chat.%')->count());
        $message = $this->create();
        ActivityMembership::query()->update(['active' => false]);
        $this->dispatch($message);
        $this->assertSame(0, Notification::where('type', 'like', 'group_chat.%')->count());
    }

    public function test_parent_and_student_http_lists_bind_only_the_matching_private_persona(): void
    {
        $student = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->dispatch($this->create());
        $this->dispatch($this->create(['audience' => 'learners']));
        $parentNotice = $this->notice($this->parent, 'parent');
        $studentNotice = $this->notice($student, 'student');
        $this->link($this->parent, $parentNotice);
        $this->link($student, $studentNotice);
        $this->app['auth']->forgetGuards();
        $response = $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/get-notification')->assertOk()->assertJsonPath('error', false);
        $row = collect($response->json('data.data'))->firstWhere('id', $parentNotice->id);
        $this->assertSame((int) $this->parent->id, $row['reader_id']);
        $this->assertSame(1, $row['school_id']);
        $this->assertNotContains((int) $studentNotice->id, array_column($response->json('data.data'), 'id'));
        $this->app['auth']->forgetGuards();
        $response = $this->withToken($this->schoolA->tokens['asha'])->getJson('/api/student/get-notification')->assertOk()->assertJsonPath('error', false);
        $row = collect($response->json('data.data'))->firstWhere('id', $studentNotice->id);
        $this->assertSame((int) $student->id, $row['reader_id']);
        $this->assertSame(1, $row['school_id']);
    }

    public function test_shared_reply_policy_withdrawal_revokes_already_queued_peer_notice(): void
    {
        $mother = User::findOrFail($this->schoolA->ids['mona_user']);
        $mother->forceFill(['email_verified_at' => now()])->save();
        $types = config('notification_types');
        $types[GroupChatNotices::FAMILY]['channels']['email'] = 'default_on';
        config(['notification_types' => $types]);
        GuardianLink::updateOrCreate(['learner_id' => $this->learner, 'guardian_user_id' => $mother->id], ['status' => 'active', 'relationship' => 'mother', 'can_approve' => true]);
        app(GroupChatRules::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'guardian_shared_replies' => true] + GroupChatRules::DEFAULTS);
        $thread = $this->create();
        $message = app(GroupChatMessages::class)->send($this->parent, (int) $this->group->id, $thread['thread_id'], [
            'operation_id' => (string) Str::uuid(), 'persona' => 'parent', 'visibility' => 'thread', 'body' => 'PRIVATE explicitly shared peer reply', 'language' => 'af']);
        $this->dispatch($message);
        $notice = $this->notice($mother, 'parent');
        $this->link($mother, $notice);
        $query = $this->visible($mother);
        $this->assertSame(1, (clone $query)->count());
        $key = DeliveryLog::queued('mail', $notice->type, (int) $mother->id, $mother->email);
        $delivery = DeliveryLog::attempt($key, 1);
        $this->assertTrue(app(DeliveryGuard::class)->allows($delivery, $notice->type, 'email', $mother->email, $notice->delivery_payload));
        app(GroupChatRules::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 1] + GroupChatRules::DEFAULTS);
        $this->assertSame(0, (clone $query)->count());
        $this->assertFalse(app(DeliveryGuard::class)->allows($delivery, $notice->type, 'email', $mother->email, $notice->delivery_payload));
        $this->assertSame([], app(Notifier::class)->send($mother, $notice->type, $notice->title, $notice->message, null, $notice));
    }

    public function test_dual_role_account_never_receives_other_persona_rows_in_parent_or_student_list(): void
    {
        $this->parent->assignRole('Student');
        Students::whereKey($this->learner)->update(['user_id' => $this->parent->id]);
        $this->dispatch($this->create());
        $this->dispatch($this->create(['audience' => 'learners']));
        $parent = $this->notice($this->parent, 'parent');
        $student = $this->notice($this->parent, 'student');
        $this->link($this->parent, $parent);
        $this->link($this->parent, $student);
        $this->app['auth']->forgetGuards();
        $rows = $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/get-notification')->assertOk()->assertJsonPath('error', false)->json('data.data');
        $this->assertContains((int) $parent->id, array_column($rows, 'id'));
        $this->assertNotContains((int) $student->id, array_column($rows, 'id'));
        $this->app['auth']->forgetGuards();
        $rows = $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/student/get-notification')->assertOk()->assertJsonPath('error', false)->json('data.data');
        $this->assertContains((int) $student->id, array_column($rows, 'id'));
        $this->assertNotContains((int) $parent->id, array_column($rows, 'id'));
    }
}
