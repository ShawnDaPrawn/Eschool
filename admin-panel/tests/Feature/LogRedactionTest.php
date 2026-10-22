<?php

namespace Tests\Feature;

use App\Services\MailService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * What the app writes to its log when something fails: no passwords, and no
 * log line that grows with the size of the request.
 */
class LogRedactionTest extends TestCase
{
    public function test_a_failed_welcome_email_is_logged_without_the_passwords(): void
    {
        Log::spy();
        Mail::shouldReceive('send')->andThrow(new \RuntimeException('SMTP is down'));

        $sent = MailService::sendWithFallback('students.email', [
            'email' => 'parent@example.org',
            'subject' => 'Welcome',
            'password' => ' Parent#Secret1',
            'child_password' => ' Child#Secret2',
            'body' => '<p>Your password is Parent#Secret1</p>',
        ], fn () => null);

        $this->assertFalse($sent);
        $logged = $this->logged('info');
        $this->assertStringContainsString('parent@example.org', $logged);
        foreach (['Parent#Secret1', 'Child#Secret2'] as $secret) {
            $this->assertStringNotContainsString($secret, $logged);
        }
    }

    /**
     * Logs carry ids only: the request is logged by its field names, never
     * their values (it used to be a curl command with every value).
     */
    public function test_a_large_request_makes_a_small_log_entry_without_values(): void
    {
        Log::spy();
        $request = Request::create('/api/student/apply-leave?token=query-secret', 'POST', [
            // Deeper than the curl log used to handle: it raised "Array to string conversion".
            'a' => ['b' => ['c' => ['d' => ['e' => 'deep-value']]]],
            'password' => 'Body#Secret3',
            'reason' => str_repeat('r', 50000),
            'items' => array_fill(0, 500, str_repeat('i', 900)),
        ]);
        $this->app->instance('request', $request);

        ResponseService::logCurlRequest();
        ResponseService::logErrorResponse(new \RuntimeException(str_repeat('m', 50000)));

        $entries = [];
        Log::shouldHaveReceived('error')->withArgs(function (string $message, array $context = []) use (&$entries) {
            // report() also logs the exception itself; that entry is the framework's.
            if (str_starts_with($message, 'Request: ') || str_contains($message, 'Params : ')) {
                $entries[] = $message.json_encode($context);
            }

            return true;
        });

        $this->assertCount(2, $entries);
        $this->assertStringStartsWith('Request: POST /api/student/apply-leave', $entries[0]);
        foreach ($entries as $entry) {
            $this->assertLessThan(25000, strlen($entry));
            foreach (['a.b.c.d.e', 'password', 'reason', 'items.*', 'token'] as $name) {
                $this->assertStringContainsString('"'.$name.'"', $entry);
            }
            foreach (['deep-value', 'Body#Secret3', 'query-secret', 'rrrrrrrr', 'iiiiiiii'] as $value) {
                $this->assertStringNotContainsString($value, $entry);
            }
        }
    }

    private function logged(string $level): string
    {
        $logged = '';
        Log::shouldHaveReceived($level)->withArgs(function (string $message, array $context = []) use (&$logged) {
            $logged .= $message.json_encode($context);

            return true;
        });

        return $logged;
    }
}
