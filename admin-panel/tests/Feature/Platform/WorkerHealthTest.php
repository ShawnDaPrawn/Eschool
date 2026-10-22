<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Providers\ObservabilityServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * The worker has no public address; its uptime check calls GET /up/worker,
 * which fails once queued jobs stop being picked up. A job that uses up its
 * tries writes one "Queued job failed" line, which the failed-job alert
 * matches (engineering/runbooks/incident.md).
 */
class WorkerHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'database', 'queue.worker_health.max_wait_seconds' => 600]);
    }

    public function test_an_empty_queue_is_healthy(): void
    {
        $this->getJson('/up/worker')
            ->assertOk()
            ->assertExactJson(['status' => 'ok', 'oldest_wait_seconds' => 0])
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_jobs_waiting_less_than_the_limit_are_healthy(): void
    {
        $this->queueJob(availableSecondsAgo: 120);
        $this->queueJob(availableSecondsAgo: -3600); // delayed until later: not due yet

        $this->getJson('/up/worker')->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_a_due_job_waiting_longer_than_the_limit_fails_the_check(): void
    {
        $this->queueJob(availableSecondsAgo: 30);
        $this->queueJob(availableSecondsAgo: 900);

        $response = $this->getJson('/up/worker');

        $response->assertStatus(503)->assertJson(['status' => 'stalled']);
        $this->assertGreaterThanOrEqual(900, $response->json('oldest_wait_seconds'));
    }

    public function test_a_job_a_worker_is_running_does_not_count_as_waiting(): void
    {
        $this->queueJob(availableSecondsAgo: 900, reserved: true);

        $this->getJson('/up/worker')->assertOk();
    }

    public function test_a_job_left_reserved_by_a_crashed_worker_counts_once_its_reservation_expires(): void
    {
        $retryAfter = (int) config('queue.connections.database.retry_after');
        $this->queueJob(availableSecondsAgo: 3600, reservedSecondsAgo: $retryAfter + 900);

        $response = $this->getJson('/up/worker');

        $response->assertStatus(503)->assertJson(['status' => 'stalled']);
        $this->assertGreaterThanOrEqual(900, $response->json('oldest_wait_seconds'));
    }

    public function test_without_a_database_queue_there_is_nothing_to_wait_for(): void
    {
        config(['queue.default' => 'sync']);

        $this->getJson('/up/worker')->assertOk()->assertExactJson(['status' => 'no-queue', 'oldest_wait_seconds' => null]);
    }

    public function test_the_check_needs_no_session_and_is_rate_limited(): void
    {
        $response = $this->getJson('/up/worker');

        $response->assertOk();
        $this->assertEmpty($response->headers->getCookies(), 'no session or XSRF cookie');
        $this->assertSame('60', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_a_job_that_uses_up_its_tries_is_logged_once_by_name_without_its_data(): void
    {
        config(['queue.default' => 'sync']);
        Log::spy();

        try {
            dispatch(function () {
                throw new RuntimeException('Mailbox for parent@example.org is full');
            });
            $this->fail('The sync queue rethrows the job exception');
        } catch (RuntimeException) {
            // expected
        }

        Log::shouldHaveReceived('error')->once()->with(
            ObservabilityServiceProvider::JOB_FAILED_MESSAGE,
            Mockery::on(function (array $context) {
                $this->assertStringStartsWith('Closure (', $context['job']);
                $this->assertSame('sync', $context['connection']);
                $this->assertSame(RuntimeException::class, $context['exception_class']);
                $this->assertStringNotContainsString('parent@example.org', json_encode($context));

                return true;
            })
        );
        $this->assertNull(Context::get('job'), 'the job name does not stay on the request\'s later lines');
    }

    public function test_lines_written_by_a_job_carry_its_name(): void
    {
        config(['queue.default' => 'sync']);

        // The queued closure is serialized, so it reports back through the cache.
        dispatch(function () {
            cache()->put('job-seen-in-context', Context::get('job'));
        });

        $seen = cache()->get('job-seen-in-context');
        $this->assertIsString($seen);
        $this->assertStringStartsWith('Closure (', $seen);
        $this->assertNull(Context::get('job'));
    }

    private function queueJob(int $availableSecondsAgo, bool $reserved = false, ?int $reservedSecondsAgo = null): void
    {
        $now = now()->getTimestamp();
        $reservedAt = $reservedSecondsAgo !== null ? $now - $reservedSecondsAgo : ($reserved ? $now : null);
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => $reservedAt !== null ? 1 : 0,
            'reserved_at' => $reservedAt,
            'available_at' => $now - $availableSecondsAgo,
            'created_at' => $now - max($availableSecondsAgo, 0),
        ]);
    }
}
