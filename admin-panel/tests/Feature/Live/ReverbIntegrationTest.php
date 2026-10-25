<?php

declare(strict_types=1);

namespace Tests\Feature\Live;

use App\Events\Consultations\ConsultationCountsChanged;
use App\Models\Consultations\Consultation;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Live\LiveSessions;
use App\Services\Platform\Events\Outbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/** Actual Reverb + database worker + RFC6455 client; opt in to local process execution. */
class ReverbIntegrationTest extends TestCase
{
    use UsesTwoSchools;

    public function test_outer_commit_reaches_an_actual_authorized_reverb_socket_within_two_seconds(): void
    {
        if (! filter_var(env('LIVE_REVERB_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Set LIVE_REVERB_INTEGRATION=true for the real local socket qualification.');
        }
        $this->setUpTwoSchools();
        config(['live.enabled' => true, 'modules.live_updates' => ['always_on' => true], 'modules.consultations' => ['always_on' => true],
            'cache.default' => 'file', 'broadcasting.connections.reverb.key' => 'qualification-live-key',
            'broadcasting.connections.reverb.secret' => str_repeat('q', 32), 'broadcasting.connections.reverb.app_id' => 'qualification-live',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1', 'broadcasting.connections.reverb.options.port' => 8086,
            'broadcasting.connections.reverb.options.scheme' => 'http', 'broadcasting.connections.reverb.options.useTLS' => false,
            'live.client.host' => '127.0.0.1', 'live.client.port' => 8086, 'live.client.scheme' => 'ws']);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $actor = User::findOrFail($this->schoolA->ids['tara_user']);
        $token = $actor->createToken('teacher');
        $actor->withAccessToken($token->accessToken);
        $request = Request::create('/api/v2/live/session', 'POST');
        $request->setUserResolver(fn () => $actor);
        $session = app(LiveSessions::class)->open($request, $actor);
        $year = $this->schoolA->ids['session_year'];
        $row = Consultation::create(['session_year_id' => $year, 'created_by' => $this->superAdmin()->id, 'type' => 'poll', 'title' => 'Synthetic local socket poll',
            'instructions' => null, 'audience' => ['kind' => 'school', 'parents' => false, 'include_staff' => true, 'session_year_id' => $year, 'target_id' => null], 'anonymous' => true,
            'results_visibility' => 'public_live', 'status' => 'open', 'opens_at' => now()->utc()->subHour(), 'closes_at' => now()->utc()->addHour(), 'revision' => 1]);
        // The actual worker reads committed fixtures and the same file-cache lease.
        DB::commit();
        $directory = sys_get_temp_dir().'/ne-reverb-'.bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $env = array_merge(getenv(), ['APP_ENV' => 'testing', 'APP_BASE_PATH' => base_path(), 'LIVE_UPDATES_ENABLED' => 'true',
            'REVERB_APP_KEY' => 'qualification-live-key', 'REVERB_APP_SECRET' => str_repeat('q', 32), 'REVERB_APP_ID' => 'qualification-live',
            'REVERB_HOST' => '127.0.0.1', 'REVERB_PORT' => '8086', 'REVERB_SCHEME' => 'http', 'REVERB_PUBLIC_HOST' => '127.0.0.1', 'REVERB_PUBLIC_PORT' => '8086', 'REVERB_PUBLIC_SCHEME' => 'ws',
            'CACHE_DRIVER' => 'file', 'QUEUE_CONNECTION' => 'database', 'LOG_CHANNEL' => 'stderr']);
        // The worker must see actual enabled school modules, rather than test-only config.
        DB::table('school_modules')->updateOrInsert(['school_id' => 1, 'module' => 'live_updates'], ['enabled' => true, 'in_package' => true, 'read_only' => false]);
        DB::table('school_modules')->updateOrInsert(['school_id' => 1, 'module' => 'consultations'], ['enabled' => true, 'in_package' => true, 'read_only' => false]);
        $processes = [];
        $start = function (array $command, string $name) use (&$processes, $directory, $env) {
            $pipes = [];
            $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $directory.'/'.$name.'.log', 'a'], 2 => ['file', $directory.'/'.$name.'.log', 'a']], $pipes, base_path(), $env);
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = $process;
        };
        try {
            $start([PHP_BINARY, 'artisan', 'reverb:start', '--host=127.0.0.1', '--port=8086'], 'reverb');
            $deadline = microtime(true) + 10;
            do {
                $socket = @fsockopen('127.0.0.1', 8086);
                if ($socket) {
                    fclose($socket);
                    break;
                }
                usleep(20000);
            } while (microtime(true) < $deadline);
            $start(['python3', 'tool/live/socket_probe.py', '8086', 'qualification-live-key', str_repeat('q', 32), $session['channel'], $directory.'/ready', $directory.'/event.json'], 'probe');
            $deadline = microtime(true) + 10;
            while (! is_file($directory.'/ready') && microtime(true) < $deadline) {
                usleep(20000);
            }
            $this->assertFileExists($directory.'/ready', 'Socket subscription failed; inspect '.$directory);
            $start([PHP_BINARY, 'artisan', 'queue:work', 'database', '--queue=live', '--sleep=1', '--tries=1'], 'worker');
            $started = microtime(true);
            DB::transaction(fn () => Outbox::record(new ConsultationCountsChanged(1, (int) $row->id, 1)));
            $deadline = $started + 5;
            while (! is_file($directory.'/event.json') && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertFileExists($directory.'/event.json', 'No event arrived; inspect '.$directory);
            $elapsed = microtime(true) - $started;
            $event = json_decode(file_get_contents($directory.'/event.json'), true, 16, JSON_THROW_ON_ERROR);
            $data = is_string($event['data']) ? json_decode($event['data'], true, 16, JSON_THROW_ON_ERROR) : $event['data'];
            $this->assertSame(['type' => 'consultations.counts_changed', 'school_id' => 1, 'consultation_id' => (int) $row->id, 'revision' => 1], $data);
            $this->assertLessThan(2.0, $elapsed, 'Local commit-to-socket target exceeded.');
            fwrite(STDERR, 'Local Reverb commit-to-socket seconds: '.number_format($elapsed, 3).PHP_EOL);
        } finally {
            foreach (array_reverse($processes) as $process) {
                proc_terminate($process);
                proc_close($process);
            }
            DB::beginTransaction();
        }
    }
}
