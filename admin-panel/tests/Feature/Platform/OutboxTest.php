<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Live\DispatchLiveOutbox;
use App\Jobs\Platform\DispatchOutbox;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\DomainEvent;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Events\OutboxListener;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use RuntimeException;
use Tests\TestCase;

/**
 * The outbox: events exist only if the transaction that recorded them
 * commits, and DispatchOutbox hands each one to its listeners exactly once,
 * retrying (and recording) a listener that fails.
 */
class OutboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        OutboxTestListener::$calls = [];
        OutboxTestListener::$failuresLeft = 0;
        config(['platform.outbox.listeners' => ['test.thing_happened' => [OutboxTestListener::class]]]);
    }

    public function test_an_event_is_stored_with_its_payload_inside_the_transaction(): void
    {
        $event = DB::transaction(fn () => Outbox::record(new ThingHappened(7)));

        $stored = OutboxEvent::sole();
        $this->assertSame($event->id, $stored->id);
        $this->assertSame('test.thing_happened', $stored->type);
        $this->assertSame(['thing_id' => 7], $stored->payload);
        $this->assertNull($stored->school_id);
        $this->assertNull($stored->dispatched_at);
        $this->assertSame(0, $stored->attempts);
    }

    public function test_a_rolled_back_transaction_leaves_no_event(): void
    {
        try {
            DB::transaction(function () {
                Outbox::record(new ThingHappened(1));

                throw new RuntimeException('The change failed');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame(0, OutboxEvent::count());
    }

    public function test_recording_outside_a_transaction_is_refused(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('inside the database transaction');

        Outbox::record(new ThingHappened(1));
    }

    public function test_each_event_goes_to_its_listeners_once(): void
    {
        DB::transaction(function () {
            Outbox::record(new ThingHappened(1));
            Outbox::record(new ThingHappened(2));
        });

        $first = (new DispatchOutbox)->handle();
        $second = (new DispatchOutbox)->handle();
        $this->assertSame('skipped', Outbox::dispatchOne((int) OutboxEvent::first()->id));

        $this->assertSame(['dispatched' => 2, 'failed' => 0, 'skipped' => 0], $first);
        $this->assertSame(['dispatched' => 0, 'failed' => 0, 'skipped' => 0], $second);
        $this->assertSame([1, 2], OutboxTestListener::$calls);
        $this->assertSame(0, OutboxEvent::whereNull('dispatched_at')->count());
    }

    public function test_an_event_without_listeners_is_marked_dispatched(): void
    {
        config(['platform.outbox.listeners' => []]);
        DB::transaction(fn () => Outbox::record(new ThingHappened(3)));

        $this->assertSame(['dispatched' => 1, 'failed' => 0, 'skipped' => 0], (new DispatchOutbox)->handle());
    }

    public function test_a_failing_listener_is_recorded_rolled_back_and_retried_after_its_backoff(): void
    {
        Log::spy();
        OutboxTestListener::$failuresLeft = 1;
        DB::transaction(fn () => Outbox::record(new ThingHappened(5)));

        $this->assertSame(['dispatched' => 0, 'failed' => 1, 'skipped' => 0], (new DispatchOutbox)->handle());

        $event = OutboxEvent::sole();
        $this->assertSame(1, $event->attempts);
        $this->assertStringContainsString('RuntimeException: Listener failed', (string) $event->last_error);
        $this->assertNull($event->dispatched_at);
        $this->assertTrue($event->available_at->isFuture());
        // The listener's write was rolled back with the failed attempt.
        $this->assertSame(0, DB::table('settings')->where('type', 'outbox-test-5')->count());
        Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context = []) => $message === 'outbox_listener_failed'
            && $context === ['event_id' => $event->id, 'type' => 'test.thing_happened', 'attempt' => 1, 'exception' => RuntimeException::class])->once();

        // Not before the backoff has passed.
        $this->assertSame(['dispatched' => 0, 'failed' => 0, 'skipped' => 0], (new DispatchOutbox)->handle());

        $this->travel(61)->seconds();
        $this->assertSame(['dispatched' => 1, 'failed' => 0, 'skipped' => 0], (new DispatchOutbox)->handle());
        $this->assertNotNull($event->refresh()->dispatched_at);
        $this->assertNull($event->last_error);
        $this->assertSame(1, DB::table('settings')->where('type', 'outbox-test-5')->count());
    }

    public function test_an_event_that_keeps_failing_is_parked(): void
    {
        Log::spy();
        config(['platform.outbox.max_attempts' => 2, 'platform.outbox.backoff' => [1]]);
        OutboxTestListener::$failuresLeft = 5;
        DB::transaction(fn () => Outbox::record(new ThingHappened(9)));

        (new DispatchOutbox)->handle();
        $this->travel(2)->seconds();
        (new DispatchOutbox)->handle();
        $this->travel(2)->seconds();
        $this->assertSame(['dispatched' => 0, 'failed' => 0, 'skipped' => 0], (new DispatchOutbox)->handle());

        $this->assertSame(2, OutboxEvent::sole()->attempts);
        Log::shouldHaveReceived('error')->withArgs(fn (string $message) => $message === 'outbox_event_parked')->once();
    }

    public function test_a_delayed_live_accelerator_cannot_resume_a_parked_event(): void
    {
        config(['platform.outbox.max_attempts' => 1, 'platform.outbox.backoff' => [1]]);
        OutboxTestListener::$failuresLeft = 1;
        $event = DB::transaction(fn () => Outbox::record(new ThingHappened(12)));
        $this->assertSame('failed', Outbox::dispatchOne((int) $event->id));
        $this->travel(2)->seconds();

        (new DispatchLiveOutbox((int) $event->id))->handle();

        $this->assertNull($event->fresh()->dispatched_at);
        $this->assertSame(1, $event->fresh()->attempts);
        $this->assertSame([], OutboxTestListener::$calls);
        $this->assertSame(0, DB::table('settings')->where('type', 'outbox-test-12')->count());
    }

    public function test_backoff_created_in_a_school_timezone_is_not_due_on_the_utc_worker(): void
    {
        $this->travelTo('2026-10-26 12:00:00 UTC');
        $timezone = date_default_timezone_get();
        try {
            date_default_timezone_set('Pacific/Honolulu');
            OutboxTestListener::$failuresLeft = 1;
            $event = DB::transaction(fn () => Outbox::record(new ThingHappened(13)));
            $this->assertSame('failed', Outbox::dispatchOne((int) $event->id));
            date_default_timezone_set('UTC');

            $this->assertSame(['dispatched' => 0, 'failed' => 0, 'skipped' => 0], Outbox::dispatchPending());
            $this->assertSame('2026-10-26 12:00:00.000000', DB::table('domain_events')->where('id', $event->id)->value('occurred_at'));
            $this->assertSame('2026-10-26 12:01:00', DB::table('domain_events')->where('id', $event->id)->value('available_at'));
            $this->travel(61)->seconds();
            $this->assertSame(['dispatched' => 1, 'failed' => 0, 'skipped' => 0], Outbox::dispatchPending());
            $this->assertSame([13], OutboxTestListener::$calls);
        } finally {
            date_default_timezone_set($timezone);
        }
    }

    public function test_a_different_school_clock_cannot_shorten_a_utc_backoff(): void
    {
        $this->travelTo('2026-10-26 12:00:00 UTC');
        $timezone = date_default_timezone_get();
        try {
            date_default_timezone_set('UTC');
            OutboxTestListener::$failuresLeft = 1;
            $event = DB::transaction(fn () => Outbox::record(new ThingHappened(14)));
            $this->assertSame('failed', Outbox::dispatchOne((int) $event->id));
            date_default_timezone_set('Pacific/Kiritimati');

            $this->assertSame(['dispatched' => 0, 'failed' => 0, 'skipped' => 0], Outbox::dispatchPending());
            $this->assertSame('skipped', Outbox::dispatchOne((int) $event->id));
            $this->travel(61)->seconds();
            $this->assertSame(['dispatched' => 1, 'failed' => 0, 'skipped' => 0], Outbox::dispatchPending());
            $this->assertSame([14], OutboxTestListener::$calls);
        } finally {
            date_default_timezone_set($timezone);
        }
    }

    public function test_the_dispatcher_is_scheduled_every_minute_and_runs_on_demand(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($event) => $event->description === 'outbox-dispatch');
        $this->assertCount(1, $events);
        $this->assertSame('* * * * *', $events->first()->expression);

        DB::transaction(fn () => Outbox::record(new ThingHappened(4)));
        $this->artisan('outbox:dispatch')->expectsOutput('Dispatched 1, failed 0, skipped 0.')->assertSuccessful();
        $this->assertSame([4], OutboxTestListener::$calls);
    }
}

class ThingHappened extends DomainEvent
{
    public function __construct(public readonly int $thingId)
    {
        parent::__construct();
    }

    public function type(): string
    {
        return 'test.thing_happened';
    }

    public function payload(): array
    {
        return ['thing_id' => $this->thingId];
    }
}

class OutboxTestListener implements OutboxListener
{
    /** @var list<int> */
    public static array $calls = [];

    public static int $failuresLeft = 0;

    public function handle(OutboxEvent $event): void
    {
        DB::table('settings')->insert(['type' => 'outbox-test-'.$event->payload['thing_id'], 'message' => '1']);
        if (self::$failuresLeft > 0) {
            self::$failuresLeft--;

            throw new RuntimeException('Listener failed');
        }
        self::$calls[] = $event->payload['thing_id'];
    }
}
