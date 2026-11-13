<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Http\Middleware\PanelRecordContext;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class PanelRecordContextTest extends TestCase
{
    public static function invalidContexts(): array
    {
        return [[[], 1], [42, []], [43, 1], [42, 2], [null, null]];
    }

    #[DataProvider('invalidContexts')]
    public function test_stale_or_malformed_context_never_reaches_the_write($account, $school): void
    {
        $request = Request::create('/my-school/preferences/quiet-hours', 'POST', ['expected_account_id' => $account, 'expected_school_id' => $school]);
        $request->setUserResolver(fn () => (new User)->forceFill(['id' => 42, 'status' => 1]));
        try {
            (new PanelRecordContext)->handle($request, function () {
                $this->fail('A refused context must not execute the write.');
            });
            $this->fail('A stale context was accepted.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
    }

    public function test_current_context_reaches_the_existing_handler(): void
    {
        $request = Request::create('/my-school/preferences/quiet-hours', 'POST', ['expected_account_id' => '42', 'expected_school_id' => '1']);
        $request->setUserResolver(fn () => (new User)->forceFill(['id' => 42, 'status' => 1]));
        $response = (new PanelRecordContext)->handle($request, fn () => new Response('allowed'));
        $this->assertSame('allowed', $response->getContent());
    }
}
