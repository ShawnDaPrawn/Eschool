<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\AssignRequestId;
use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use PDOException;
use RuntimeException;
use Tests\TestCase;

/**
 * The json log channel (LOG_CHANNEL=json, used on Cloud Run): one JSON object
 * per line with the request id, user id, school id and route, exceptions in a
 * shape Error Reporting reads, and nothing personal beyond ids. See also
 * LogRedactionTest, which covers what the app chooses to log.
 */
class StructuredLoggingTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logFile = tempnam(sys_get_temp_dir(), 'json-log-');
        config([
            'logging.default' => 'json',
            'logging.channels.json.with.stream' => $this->logFile,
            'logging.channels.json.level' => 'debug',
            'logging.channels.json.formatter_with.project' => 'navigateed-test',
            'logging.channels.json.formatter_with.service' => 'navigateed-admin',
            'logging.channels.json.formatter_with.version' => 'v0.0.0-test',
        ]);
        Log::forgetChannel('json');
    }

    protected function tearDown(): void
    {
        Log::forgetChannel('json');
        @unlink($this->logFile);

        parent::tearDown();
    }

    public function test_a_log_line_is_one_json_object_with_the_request_fields(): void
    {
        Log::warning('Import finished with skipped rows', ['student_id' => 42, 'skipped' => 3, 'status' => 'partial']);

        $entry = $this->onlyEntry();
        $this->assertSame('WARNING', $entry['severity']);
        $this->assertSame('Import finished with skipped rows', $entry['message']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d\.\d{6}Z$/', $entry['time']);
        foreach (['request_id', 'user_id', 'school_id', 'route', 'method'] as $field) {
            $this->assertArrayHasKey($field, $entry);
            $this->assertNull($entry[$field], "$field outside a request");
        }
        $this->assertSame(['student_id' => 42, 'skipped' => 3, 'status' => 'partial'], $entry['context']);
    }

    public function test_every_response_carries_a_request_id(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', (string) $response->headers->get(AssignRequestId::HEADER));
        $this->assertNotSame($response->headers->get(AssignRequestId::HEADER), $this->get('/up')->headers->get(AssignRequestId::HEADER));
    }

    public function test_a_plain_incoming_request_id_is_kept_and_anything_else_replaced(): void
    {
        $kept = $this->withHeaders([AssignRequestId::HEADER => 'app-7f3a9c21.42'])->get('/up');
        $this->assertSame('app-7f3a9c21.42', $kept->headers->get(AssignRequestId::HEADER));

        foreach (['short', str_repeat('a', 129), 'has spaces in it', '<script>alert(1)</script>'] as $bad) {
            $replaced = $this->withHeaders([AssignRequestId::HEADER => $bad])->get('/up')->headers->get(AssignRequestId::HEADER);
            $this->assertNotSame($bad, $replaced);
            $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string) $replaced);
        }
    }

    public function test_log_lines_of_a_request_carry_its_id_route_user_and_trace(): void
    {
        Route::middleware('auth')->get('/_test/structured-log/{student}', function (string $student) {
            Log::info('Opened a learner page', ['student_id' => (int) $student]);

            return response('ok');
        });
        $user = new User;
        $user->id = 123;

        $response = $this->actingAs($user)
            ->withHeaders(['X-Cloud-Trace-Context' => '105445aa7843bc8bf206b12000100000/1;o=1'])
            ->get('/_test/structured-log/7');

        $response->assertOk();
        $entry = $this->onlyEntry();
        $this->assertSame($response->headers->get(AssignRequestId::HEADER), $entry['request_id']);
        $this->assertSame('_test/structured-log/{student}', $entry['route']);
        $this->assertSame('GET', $entry['method']);
        $this->assertSame(123, $entry['user_id']);
        $this->assertNull($entry['school_id']);
        $this->assertSame('projects/navigateed-test/traces/105445aa7843bc8bf206b12000100000', $entry['logging.googleapis.com/trace']);
        $this->assertSame(['student_id' => 7], $entry['context']);
    }

    public function test_request_bodies_and_personal_data_never_reach_the_log(): void
    {
        Route::post('/_test/structured-log', function () {
            // What ResponseService does when an API call fails: report the
            // exception, then log the (secret-redacted) request parameters.
            ResponseService::logErrorResponse(new RuntimeException('Could not save the leave application'));
            Log::warning('Sign-in refused after too many wrong passwords', [
                'account' => request('email'),
                'ip' => request()->ip(),
                'user_id' => 55,
                'mobile' => 821234567,
                'input' => request()->all(),
            ]);

            return response('ok');
        });

        $this->post('/_test/structured-log', [
            'student_id' => 7,
            'first_name' => 'Thandiwe',
            'email' => 'thandiwe.parent@example.org',
            'id_number' => '0101015009087',
            'reason' => 'Family funeral in Mthatha',
            'password' => 'Body#Secret3',
            'nested' => ['note' => 'Allergic to penicillin'],
        ])->assertOk();

        $log = (string) file_get_contents($this->logFile);
        foreach (['Thandiwe', 'thandiwe.parent@example.org', '0101015009087', 'Mthatha', 'Body#Secret3', 'penicillin', '821234567', '127.0.0.1'] as $personal) {
            $this->assertStringNotContainsString($personal, $log);
        }

        $entries = $this->entries();
        $this->assertCount(3, $entries, 'the reported exception, the parameters line and the sign-in line');
        $parameters = $entries[1];
        $this->assertStringStartsWith('Could not save the leave application', $parameters['message']);
        $this->assertSame(7, (int) $parameters['context']['student_id']);
        $this->assertSame('[omitted]', $parameters['context']['first_name']);
        $this->assertSame(['note' => '[omitted]'], $parameters['context']['nested']);
        $signIn = $entries[2]['context'];
        $this->assertSame(55, $signIn['user_id']);
        $this->assertSame('[omitted]', $signIn['account']);
        $this->assertSame('[omitted]', $signIn['ip']);
        $this->assertSame('[omitted]', $signIn['mobile']);
    }

    public function test_personal_data_in_a_message_is_masked(): void
    {
        Log::error('Email sending failed: 550 mailbox unavailable <parent@example.org>, ID 0101015009087, call 0821234567 or +27821234567');

        $message = $this->onlyEntry()['message'];
        $this->assertSame('Email sending failed: 550 mailbox unavailable <[email]>, ID [id-number], call [phone] or [phone]', $message);
    }

    public function test_an_exception_is_written_for_error_reporting_without_arguments_or_bound_values(): void
    {
        $leak = fn (string $password) => throw new RuntimeException('Token exchange failed');
        try {
            $leak('Hunter2-Secret');
        } catch (RuntimeException $e) {
            Log::error($e->getMessage(), ['exception' => $e]);
        }

        $entry = $this->onlyEntry();
        $this->assertSame('ERROR', $entry['severity']);
        $this->assertSame('type.googleapis.com/google.devtools.clouderrorreporting.v1beta1.ReportedErrorEvent', $entry['@type']);
        $this->assertSame(['service' => 'navigateed-admin', 'version' => 'v0.0.0-test'], $entry['serviceContext']);
        $this->assertStringStartsWith('PHP Fatal error:  Uncaught RuntimeException: Token exchange failed in '.__FILE__, $entry['stack_trace']);
        $this->assertStringContainsString("\nStack trace:\n#0 ", $entry['stack_trace']);
        $this->assertSame(__FILE__, $entry['context']['reportLocation']['filePath']);
        $this->assertSame(RuntimeException::class, $entry['exception']['class']);
        $this->assertStringNotContainsString('Hunter2-Secret', (string) file_get_contents($this->logFile));

        // A query error's message quotes the bound values and the database's own
        // text ("Duplicate entry '...'"); only the SQL with placeholders is kept.
        file_put_contents($this->logFile, '');
        $query = new QueryException(
            'mysql',
            'insert into users (email) values (?)',
            ['thandiwe.parent@example.org'],
            new PDOException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'thandiwe.parent@example.org'", 23000)
        );
        Log::error($query->getMessage(), ['exception' => $query]);

        $entry = $this->onlyEntry();
        $this->assertSame('Database error SQLSTATE[23000] (SQL: insert into users (email) values (?))', $entry['message']);
        $this->assertStringContainsString('insert into users (email) values (?)', $entry['stack_trace']);
        $this->assertStringNotContainsString('thandiwe', (string) file_get_contents($this->logFile));
    }

    public function test_a_warning_with_an_exception_is_not_sent_to_error_reporting(): void
    {
        Log::warning('Retrying the push', ['exception' => new RuntimeException('FCM timed out'), 'attempt' => 2]);

        $entry = $this->onlyEntry();
        $this->assertArrayNotHasKey('@type', $entry);
        $this->assertArrayNotHasKey('stack_trace', $entry);
        $this->assertSame(RuntimeException::class, $entry['exception']['class']);
        $this->assertSame(['attempt' => 2], $entry['context']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function entries(): array
    {
        $lines = array_values(array_filter(explode("\n", (string) file_get_contents($this->logFile))));

        return array_map(function (string $line) {
            $entry = json_decode($line, true);
            $this->assertIsArray($entry, 'Every line is a JSON object: '.$line);

            return $entry;
        }, $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private function onlyEntry(): array
    {
        $entries = $this->entries();
        $this->assertCount(1, $entries);

        return $entries[0];
    }
}
