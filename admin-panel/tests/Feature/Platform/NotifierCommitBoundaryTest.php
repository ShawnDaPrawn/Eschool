<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\DispatchOutbox;
use App\Models\Notification;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Notifications\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class NotifierCommitBoundaryTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        // Exercise real outer commits, rather than a test-owned transaction.
        // Each test removes its committed synthetic rows; fixture rows remain.
        DB::rollBack();
        config(['queue.default' => 'sync']);
        Queue::fake();
    }

    public function test_sync_acceleration_never_precedes_an_outer_rollback(): void
    {
        $before = OutboxEvent::count();
        $noticeId = null;
        try {
            DB::transaction(function () use (&$noticeId) {
                $notice = app(Notifier::class)->makeNotice('comms.notice', 'Synthetic rollback notice', 'Open the current record', '/notifications');
                $noticeId = (int) $notice->id;
                app(Notifier::class)->assign($notice, [$this->schoolA->ids['farid_user']]);
                Queue::assertNotPushed(DispatchOutbox::class);
                throw new RuntimeException('Synthetic final authorization refused');
            });
            $this->fail('The outer transaction must roll back.');
        } catch (RuntimeException $error) {
            $this->assertSame('Synthetic final authorization refused', $error->getMessage());
        }
        Queue::assertNotPushed(DispatchOutbox::class);
        $this->assertSame($before, OutboxEvent::count());
        $this->assertFalse(Notification::whereKey($noticeId)->exists());
    }

    public function test_sync_acceleration_occurs_once_after_the_real_outer_commit(): void
    {
        $noticeId = null;
        $eventId = null;
        try {
            DB::transaction(function () use (&$noticeId, &$eventId) {
                $notice = app(Notifier::class)->makeNotice('comms.notice', 'Synthetic committed notice', 'Open the current record', '/notifications');
                $noticeId = (int) $notice->id;
                DB::transaction(function () use ($notice) {
                    app(Notifier::class)->assign($notice, [$this->schoolA->ids['farid_user']]);
                    Queue::assertNotPushed(DispatchOutbox::class);
                });
                Queue::assertNotPushed(DispatchOutbox::class);
                $eventId = (int) OutboxEvent::orderByDesc('id')->value('id');
            });
            Queue::assertPushed(DispatchOutbox::class, 1);
            $this->assertTrue(Notification::whereKey($noticeId)->exists());
            $this->assertTrue(OutboxEvent::whereKey($eventId)->exists());
        } finally {
            if ($eventId !== null) {
                OutboxEvent::whereKey($eventId)->delete();
            }
            if ($noticeId !== null) {
                Notification::whereKey($noticeId)->delete();
            }
        }
    }
}
