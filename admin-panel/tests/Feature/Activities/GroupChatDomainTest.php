<?php

declare(strict_types=1);

namespace Tests\Feature\Activities;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\Activities\Chat\GroupChatMessage;
use App\Models\Activities\Chat\GroupChatRead;
use App\Models\Activities\Chat\GroupChatThread;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Students;
use App\Models\User;
use App\Services\Activities\Chat\GroupChatMessages;
use App\Services\Activities\Chat\GroupChatRules;
use App\Services\Activities\Chat\GroupChatThreads;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class GroupChatDomainTest extends TestCase
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

    private function start(array $input = [], ?User $actor = null): array
    {
        return app(GroupChatThreads::class)->create($actor ?? $this->teacher, (int) $this->group->id,
            $input + ['operation_id' => (string) Str::uuid(), 'persona' => 'staff', 'kind' => 'announcement', 'audience' => 'learners',
                'title' => 'Synthetic private thread', 'body' => 'Synthetic group staff text', 'language' => 'en']);
    }

    private function send(User $actor, array $thread, string $persona, string $body, array $input = []): array
    {
        return app(GroupChatMessages::class)->send($actor, (int) $this->group->id, $thread['thread_id'],
            $input + ['operation_id' => (string) Str::uuid(), 'persona' => $persona, 'body' => $body, 'language' => 'en', 'visibility' => $persona === 'staff' ? 'thread' : 'staff']);
    }

    private function read(User $actor, array $thread, string $persona): array
    {
        return app(GroupChatMessages::class)->read($actor, (int) $this->group->id, $thread['thread_id'], $persona);
    }

    private function denied(callable $call): void
    {
        try {
            $call();
            $this->fail('An unauthorized conversation source was accepted.');
        } catch (ModelNotFoundException) {
            $this->addToAssertionCount(1);
        } catch (HttpExceptionInterface $error) {
            $this->assertContains($error->getStatusCode(), [403, 404]);
        }
    }

    private function otherStudent(): User
    {
        $id = $this->schoolA->ids['ben'];
        Enrolment::create(['learner_id' => $id, 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        ActivityMembership::create(['activity_group_id' => $this->group->id, 'learner_id' => $id, 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic group', 'revision' => 1]);
        $mother = User::findOrFail($this->schoolA->ids['mona_user']);
        $mother->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $id, 'guardian_user_id' => $mother->id], ['status' => 'active', 'can_approve' => true]);
        app(ApprovalService::class)->decide($mother, Students::findOrFail($id), 'approve', config('identity.consent_version'));

        return User::findOrFail($this->schoolA->ids['ben_user']);
    }

    public function test_current_student_replies_are_private_to_assigned_staff_and_exact_operations_replay(): void
    {
        $other = $this->otherStudent();
        $student = User::findOrFail($this->schoolA->ids['asha_user']);
        $id = (string) Str::uuid();
        $thread = $this->start(['operation_id' => $id]);
        $this->assertSame($thread, $this->start(['operation_id' => $id]));
        $replyId = (string) Str::uuid();
        $reply = $this->send($student, $thread, 'student', 'Synthetic private learner reply', ['operation_id' => $replyId]);
        $this->assertSame($reply, $this->send($student, $thread, 'student', 'Synthetic private learner reply', ['operation_id' => $replyId]));
        $this->assertCount(2, $this->read($student, $thread, 'student')['items']);
        $this->assertCount(2, $this->read($this->teacher, $thread, 'staff')['items']);
        $this->assertCount(1, $this->read($other, $thread, 'student')['items']);
        $this->denied(fn () => app(GroupChatMessages::class)->source($other, (int) $this->group->id, $thread['thread_id'], $reply['message_id'], 'student'));
        $source = app(GroupChatMessages::class)->source($this->teacher, (int) $this->group->id, $thread['thread_id'], $reply['message_id'], 'staff');
        $this->assertSame('Synthetic private learner reply', $source['body']);
        $this->assertSame('en', $source['language']);
        $this->assertStringNotContainsString('Synthetic', DB::table('activity_chat_messages')->where('id', $reply['message_id'])->value('body'));
        $this->assertStringNotContainsString('Synthetic', DB::table('activity_chat_threads')->value('title'));
        $this->assertSame(2, GroupChatMessage::count());
        $events = OutboxEvent::where('type', 'activity.group_changed')->get();
        $this->assertCount(2, $events);
        foreach ($events as $event) {
            $this->assertSame(['group_id', 'revision'], array_keys($event->payload));
        }
    }

    public function test_family_private_questions_and_staff_replies_never_enter_other_family_counts_or_text(): void
    {
        $this->otherStudent();
        $mother = User::findOrFail($this->schoolA->ids['mona_user']);
        $thread = $this->start(['persona' => 'parent', 'kind' => 'question'], $this->parent);
        $this->send($this->teacher, $thread, 'staff', 'Synthetic private staff answer', ['reply_to' => $thread['message_id']]);
        $this->assertCount(2, $this->read($this->parent, $thread, 'parent')['items']);
        $this->assertCount(2, $this->read($this->teacher, $thread, 'staff')['items']);
        $this->assertSame([], app(GroupChatThreads::class)->listing($mother, (int) $this->group->id, 'parent')['items']);
        $this->denied(fn () => $this->read($mother, $thread, 'parent'));
        $this->denied(fn () => $this->read($this->parent, $thread, 'student'));
    }

    public function test_explicit_peer_policy_allows_shared_replies_and_withdrawal_hides_them_and_refuses_replay(): void
    {
        $other = $this->otherStudent();
        $student = User::findOrFail($this->schoolA->ids['asha_user']);
        $thread = $this->start();
        $this->denied(fn () => $this->send($student, $thread, 'student', 'Synthetic share', ['visibility' => 'thread']));
        $policy = GroupChatRules::DEFAULTS;
        app(GroupChatRules::class)->save($this->admin, $policy = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'learner_shared_replies' => true] + $policy);
        $reply = ['operation_id' => (string) Str::uuid(), 'visibility' => 'thread'];
        $this->send($student, $thread, 'student', 'Synthetic share', $reply);
        $this->assertCount(2, $this->read($other, $thread, 'student')['items']);
        app(GroupChatRules::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 1] + GroupChatRules::DEFAULTS);
        $this->assertCount(1, $this->read($other, $thread, 'student')['items']);
        $this->denied(fn () => $this->send($student, $thread, 'student', 'Synthetic share', $reply));
    }

    public function test_managers_platform_and_wrong_school_have_no_body_shortcut_and_converted_roles_cannot_borrow_rows(): void
    {
        $thread = $this->start();
        $this->denied(fn () => $this->read($this->admin, $thread, 'staff'));
        $this->admin->assignRole('Teacher');
        $this->denied(fn () => $this->read($this->admin, $thread, 'staff'));
        $this->denied(fn () => app(CurrentSchool::class)->run(2, fn () => $this->read($this->teacher, $thread, 'staff')));
        $this->denied(fn () => app(CurrentSchool::class)->acrossSchools(fn () => $this->read($this->teacher, $thread, 'staff')));
        PlatformStaffIdentity::create(['user_id' => $this->teacher->id, 'active' => true]);
        $this->denied(fn () => $this->read($this->teacher, $thread, 'staff'));
        PlatformStaffIdentity::query()->delete();
        $this->parent->assignRole('Teacher');
        $parentThread = $this->start(['audience' => 'guardians']);
        $this->assertCount(1, $this->read($this->parent, $parentThread, 'parent')['items']);
        $this->denied(fn () => $this->read($this->parent, $parentThread, 'staff'));
        ActivityStaff::create(['activity_group_id' => $this->group->id, 'user_id' => $this->parent->id, 'role' => 'facilitator', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic explicit dual-role allocation', 'revision' => 1]);
        $staffPrivate = $this->start(['kind' => 'question', 'audience' => 'guardians'], $this->parent);
        $this->assertCount(1, $this->read($this->parent, $staffPrivate, 'staff')['items']);
        $this->denied(fn () => $this->read($this->parent, $staffPrivate, 'parent'));
        $familyPrivate = $this->start(['kind' => 'question', 'persona' => 'parent'], $this->parent);
        $this->assertCount(1, $this->read($this->parent, $familyPrivate, 'parent')['items']);
        $this->parent->syncRoles(['Facilitator']);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->denied(fn () => $this->read($this->parent, $parentThread, 'parent'));
        foreach (DB::getQueryLog() as $sql) {
            $this->assertStringNotContainsString('from `activity_chat_', $sql['query']);
        }
        DB::disableQueryLog();
        $student = User::findOrFail($this->schoolA->ids['asha_user']);
        $student->syncRoles(['Facilitator']);
        $this->denied(fn () => $this->read($student, $thread, 'student'));
        $this->denied(fn () => $this->read($student, $thread, 'staff'));
    }

    public function test_current_group_and_learner_membership_retire_replies_and_queued_source_references(): void
    {
        $thread = $this->start();
        $student = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->assertCount(1, $this->read($student, $thread, 'student')['items']);
        SchoolMembership::where('user_id', $student->id)->where('role', 'Student')->update(['status' => 'suspended']);
        $this->denied(fn () => $this->read($student, $thread, 'student'));
        ActivityStaff::where('user_id', $this->teacher->id)->update(['ends_on' => '2026-02-09']);
        $this->denied(fn () => $this->read($this->teacher, $thread, 'staff'));
    }

    public function test_close_moderation_and_persona_read_cursors_preserve_private_history_without_duplicate_effects(): void
    {
        $thread = $this->start();
        $student = User::findOrFail($this->schoolA->ids['asha_user']);
        $reply = $this->send($student, $thread, 'student', 'Synthetic removed text');
        $service = app(GroupChatMessages::class);
        $this->assertSame(['thread_id' => $thread['thread_id'], 'last_message_id' => $reply['message_id']], $service->markRead($student, (int) $this->group->id, $thread['thread_id'], 'student', $reply['message_id']));
        $service->markRead($student, (int) $this->group->id, $thread['thread_id'], 'student', $thread['message_id']);
        $this->assertSame($reply['message_id'], (int) GroupChatRead::value('last_message_id'));
        $remove = ['operation_id' => (string) Str::uuid(), 'persona' => 'staff', 'revision' => 1, 'reason' => 'Synthetic private moderation reason'];
        $first = $service->remove($this->teacher, (int) $this->group->id, $thread['thread_id'], $reply['message_id'], $remove);
        $this->assertSame($first, $service->remove($this->teacher, (int) $this->group->id, $thread['thread_id'], $reply['message_id'], $remove));
        $this->assertNull(DB::table('activity_chat_messages')->where('id', $reply['message_id'])->value('body'));
        $this->assertStringNotContainsString('Synthetic', DB::table('activity_chat_messages')->where('id', $reply['message_id'])->value('removal_reason'));
        $this->denied(fn () => $service->source($student, (int) $this->group->id, $thread['thread_id'], $reply['message_id'], 'student'));
        $this->assertNull($this->read($student, $thread, 'student')['items'][1]['body']);
        app(GroupChatThreads::class)->close($this->teacher, (int) $this->group->id, $thread['thread_id'], ['operation_id' => (string) Str::uuid(), 'persona' => 'staff', 'revision' => 1, 'closed' => true]);
        $this->expectException(HttpException::class);
        $this->send($student, $thread, 'student', 'Synthetic forbidden after close');
    }

    public function test_outer_rollback_and_retention_remove_private_content_even_with_modules_disabled(): void
    {
        $before = OutboxEvent::count();
        DB::beginTransaction();
        $this->start();
        DB::rollBack();
        $this->assertSame(0, GroupChatThread::count());
        $this->assertSame(0, GroupChatMessage::count());
        $this->assertSame($before, OutboxEvent::count());
        $thread = $this->start();
        GroupChatMessage::query()->update(['sent_at' => now()->utc()->subDays(31)]);
        GroupChatThread::query()->update(['last_message_at' => now()->utc()->subDays(31)]);
        $this->denied(fn () => $this->read($this->teacher, $thread, 'staff'));
        config(['modules.groups' => ['default' => false], 'modules.chat' => ['default' => false]]);
        $this->assertSame(2, app(GroupChatThreads::class)->prune());
        $this->assertNull(DB::table('activity_chat_messages')->value('body'));
        $this->assertNull(DB::table('activity_chat_threads')->value('title'));
    }

    public function test_only_dated_ancestor_admin_cascade_can_open_child_conversation(): void
    {
        $thread = $this->start();
        $ancestor = ActivityGroup::create(['session_year_id' => $this->group->session_year_id, 'name' => 'Synthetic parent group', 'discipline' => 'Culture', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
        $this->group->update(['parent_id' => $ancestor->id]);
        ActivityStaff::where('user_id', $this->teacher->id)->update(['activity_group_id' => $ancestor->id, 'cascade' => true, 'role' => 'facilitator']);
        $this->denied(fn () => $this->read($this->teacher, $thread, 'staff'));
        ActivityStaff::where('user_id', $this->teacher->id)->update(['role' => 'admin']);
        $this->assertCount(1, $this->read($this->teacher, $thread, 'staff')['items']);
        ActivityStaff::where('user_id', $this->teacher->id)->update(['ends_on' => '2026-02-09']);
        $this->denied(fn () => $this->read($this->teacher, $thread, 'staff'));
    }

    public function test_readonly_keeps_read_and_reasoned_removal_but_denies_new_posts(): void
    {
        $thread = $this->start();
        config(['modules.groups' => ['default' => true, 'legacy' => true]]);
        SchoolModule::updateOrCreate(['school_id' => 1, 'module' => 'groups'], ['enabled' => true, 'read_only' => true, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        $this->assertCount(1, $this->read($this->teacher, $thread, 'staff')['items']);
        $this->denied(fn () => $this->send($this->teacher, $thread, 'staff', 'Synthetic disallowed write'));
        app(GroupChatMessages::class)->remove($this->teacher, (int) $this->group->id, $thread['thread_id'], $thread['message_id'],
            ['operation_id' => (string) Str::uuid(), 'persona' => 'staff', 'revision' => 1, 'reason' => 'Synthetic privacy withdrawal']);
        $this->assertNull(DB::table('activity_chat_messages')->value('body'));
    }

    public function test_exact_message_retry_cannot_replace_text_or_replay_after_assignment_withdrawal(): void
    {
        $thread = $this->start();
        $input = ['operation_id' => (string) Str::uuid()];
        $this->send($this->teacher, $thread, 'staff', 'Synthetic original immutable text', $input);
        try {
            $this->send($this->teacher, $thread, 'staff', 'Synthetic changed text', $input);
            $this->fail('An operation UUID replaced its original message.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
        $this->assertSame(2, GroupChatMessage::count());
        ActivityStaff::where('user_id', $this->teacher->id)->update(['active' => false]);
        $this->denied(fn () => $this->send($this->teacher, $thread, 'staff', 'Synthetic original immutable text', $input));
        $this->assertSame(2, GroupChatMessage::count());
    }
}
