<?php

declare(strict_types=1);

namespace Tests\Feature\Assistance;

use App\Models\Assistance\AssistanceNoticeIntent;
use App\Models\Assistance\SupportThread;
use App\Models\Notification;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolModule;
use App\Models\UserNotification;
use App\Services\Assistance\AssistanceNotices;
use App\Services\Assistance\SchoolSupportThreads;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

class AssistanceNoticesTest extends TestCase
{
    use UsesPracticeScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'ask_school'], ['in_package' => true, 'enabled' => true]);
        foreach ([$this->actor('farid'), $this->superAdmin()] as $actor) {
            $actor->forceFill(['password_change_required' => false])->save();
        }
        Queue::fake();
    }

    private function open(): array
    {
        return app(SchoolSupportThreads::class)->open($this->actor('farid'), $this->schoolA->ids['asha'], [
            'operation_id' => (string) Str::uuid(), 'text' => 'PRIVATE-HANDOFF Parent question', 'language' => 'en',
        ]);
    }

    private function parentNotice(): Notification
    {
        $thread = $this->open();
        app(SchoolSupportThreads::class)->claim($this->superAdmin(), $thread['id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1]);

        return Notification::where('type', AssistanceNotices::PARENT)->sole();
    }

    private function inbox(Notification $notice, int $actor)
    {
        UserNotification::firstOrCreate(['notification_id' => $notice->id, 'user_id' => $actor]);

        return NotificationVisibility::forUser(Notification::whereKey($notice->id), $actor, 4);
    }

    public function test_exact_open_and_claim_replay_keep_one_generic_notice_per_revision_and_recipient(): void
    {
        $input = ['operation_id' => (string) Str::uuid(), 'text' => 'PRIVATE-HANDOFF Parent question', 'language' => 'en'];
        $thread = app(SchoolSupportThreads::class)->open($this->actor('farid'), $this->schoolA->ids['asha'], $input);
        $this->assertSame($thread, app(SchoolSupportThreads::class)->open($this->actor('farid'), $this->schoolA->ids['asha'], $input));
        $notice = Notification::where('type', AssistanceNotices::OFFICE)->sole();
        $this->assertSame(['link', 'assistance_intent_id'], array_keys($notice->delivery_payload));
        $this->assertTrue(Str::isUuid($notice->delivery_payload['assistance_intent_id']));
        $this->assertStringNotContainsString($thread['id'], json_encode($notice->delivery_payload));
        $this->assertStringNotContainsString('PRIVATE-HANDOFF', $notice->title.$notice->message.json_encode($notice->delivery_payload));
        $query = $this->inbox($notice, (int) $this->superAdmin()->id);
        $this->assertSame(1, (clone $query)->count());
        $claim = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1];
        $one = app(SchoolSupportThreads::class)->claim($this->superAdmin(), $thread['id'], $claim);
        $this->assertSame($one, app(SchoolSupportThreads::class)->claim($this->superAdmin(), $thread['id'], $claim));
        $this->assertSame(0, (clone $query)->count());
        $this->assertSame(1, Notification::where('type', AssistanceNotices::PARENT)->count());
        Queue::assertNothingPushed(); // Real outer test transaction has not committed.
    }

    public function test_prepared_parent_inbox_and_queued_delivery_recheck_guardian_source_without_loading_text(): void
    {
        $notice = $this->parentNotice();
        $parent = $this->actor('farid');
        $query = $this->inbox($notice, (int) $parent->id);
        $this->assertSame(1, (clone $query)->count());
        app(NotificationRules::class)->choose($parent, $notice->type, 'email', true);
        $key = DeliveryLog::queued('mail', $notice->type, (int) $parent->id, $parent->email);
        $delivery = DeliveryLog::attempt($key, 1);
        $this->assertTrue(app(DeliveryGuard::class)->allows($delivery, $notice->type, 'email', $parent->email, $notice->delivery_payload));
        GuardianLink::where('guardian_user_id', $parent->id)->update(['status' => 'inactive']);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame(0, (clone $query)->count());
        foreach (DB::getQueryLog() as $query) {
            if (str_contains($query['query'], 'from `school_support_messages`')) {
                $this->assertStringNotContainsString('`text`', $query['query']);
            }
        }
        DB::disableQueryLog();
        $this->assertFalse(app(DeliveryGuard::class)->allows($delivery, $notice->type, 'email', $parent->email, $notice->delivery_payload));
    }

    public function test_wrong_school_owner_role_and_expired_intents_fail_closed(): void
    {
        $notice = $this->parentNotice();
        $service = app(AssistanceNotices::class);
        $this->assertFalse($service->allows($this->actor('mona'), $notice->type, $notice->delivery_payload));
        $this->assertFalse($service->allows($this->actor('tara'), $notice->type, $notice->delivery_payload));
        $parent = $this->actor('farid');
        $this->assertFalse(app(CurrentSchool::class)->run(2, fn () => $service->allows($parent, $notice->type, $notice->delivery_payload)));
        $this->assertFalse($service->allows($this->actor('farid'), AssistanceNotices::OFFICE, $notice->delivery_payload));
        $this->assertFalse($service->allows($this->actor('farid'), $notice->type, ['link' => '/other', 'assistance_intent_id' => $notice->delivery_payload['assistance_intent_id']]));
        AssistanceNoticeIntent::whereKey($notice->delivery_payload['assistance_intent_id'])->update(['expires_at' => now()->utc()->subSecond()]);
        $this->assertSame(0, $this->inbox($notice, (int) $this->actor('farid')->id)->count());
        $this->assertGreaterThanOrEqual(1, $service->prune());
    }

    public function test_office_assignment_and_role_withdrawal_retire_prepared_notice_queries(): void
    {
        $thread = $this->open();
        app(SchoolSupportThreads::class)->claim($this->superAdmin(), $thread['id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1]);
        app(SchoolSupportThreads::class)->reply($this->actor('farid'), $thread['id'], ['operation_id' => (string) Str::uuid(), 'text' => 'PRIVATE later message', 'language' => 'en']);
        $intent = AssistanceNoticeIntent::where('type', AssistanceNotices::OFFICE)->where('queue_only', false)->sole();
        $notice = Notification::findOrFail($intent->notification_id);
        $query = $this->inbox($notice, (int) $this->superAdmin()->id);
        $this->assertSame(1, (clone $query)->count());
        $this->superAdmin()->syncRoles(['Teacher']);
        $this->assertSame(0, (clone $query)->count());
        $this->assertFalse(app(AssistanceNotices::class)->allows($this->superAdmin(), $notice->type, $notice->delivery_payload));
    }

    public function test_outer_rollback_removes_handoff_notice_intent_and_outbox_together(): void
    {
        $before = AssistanceNoticeIntent::count();
        DB::beginTransaction();
        $thread = $this->open();
        $this->assertGreaterThan($before, AssistanceNoticeIntent::count());
        DB::rollBack();
        $this->assertSame($before, AssistanceNoticeIntent::count());
        $this->assertFalse(SupportThread::whereKey($thread['id'])->exists());
        Queue::assertNothingPushed();
    }

    public function test_parent_notification_action_uses_actual_recipient_and_never_exposes_private_payload(): void
    {
        $notice = $this->parentNotice();
        $parent = $this->actor('farid');
        UserNotification::firstOrCreate(['notification_id' => $notice->id, 'user_id' => $parent->id]);
        $response = $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/get-notification')->assertOk();
        $row = collect($response->json('data.data'))->firstWhere('id', $notice->id);
        $this->assertNotNull($row);
        $this->assertSame((int) $parent->id, $row['reader_id']);
        $this->assertSame(1, $row['school_id']);
        $this->assertArrayNotHasKey('delivery_payload', $row);
        $response->assertDontSee('PRIVATE-HANDOFF')->assertDontSee('assistance_intent_id');
    }

    public function test_notice_expiry_prunes_even_after_school_module_withdrawal_without_erasing_conversations(): void
    {
        $this->parentNotice();
        $this->assertGreaterThan(0, AssistanceNoticeIntent::count());
        SchoolModule::where('module', 'ask_school')->update(['enabled' => false]);
        $this->travel(31)->days();
        $this->artisan('assistance:prune-private')->assertExitCode(0);
        $this->assertSame(0, AssistanceNoticeIntent::count());
        $this->assertSame(1, SupportThread::whereNull('purged_at')->count());
    }
}
