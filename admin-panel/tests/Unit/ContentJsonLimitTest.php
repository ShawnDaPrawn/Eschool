<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\LimitRequestInput;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ContentJsonLimitTest extends TestCase
{
    private function request(string $path, string $method, string $body, ?int $declared = null): Response
    {
        $request = Request::create($path, $method, [], [], [], ['CONTENT_TYPE' => 'application/json', 'CONTENT_LENGTH' => $declared ?? strlen($body)], $body);

        return app(LimitRequestInput::class)->handle($request, fn () => response('within limit'));
    }

    public function test_draft_limit_accounts_for_escaping_and_envelope_without_changing_other_routes(): void
    {
        $body = json_encode(['markdown' => str_repeat("\x01", 2 * 1024 * 1024), 'selection' => ['title' => 'Fractions', 'group_ids' => [1], 'topic_ids' => []], 'operation_id' => '08a76889-e830-4bb8-8b8a-5d5b2b389516', 'item_revision' => 1, 'version_revision' => 1], JSON_THROW_ON_ERROR);
        $this->assertGreaterThan(12 * 1024 * 1024, strlen($body));
        $this->assertSame(200, $this->request('/api/v2/academics/content/versions/12', 'PUT', $body)->getStatusCode());
        foreach ([['PUT', '/api/v2/academics/content/versions/12/retry'], ['POST', '/api/v2/academics/content/versions/12'], ['PUT', '/api/v2/me/profile']] as [$method,$path]) {
            $this->assertSame(413, $this->request($path, $method, $body)->getStatusCode());
        }
    }

    public function test_oversize_body_is_refused_even_when_the_length_header_is_understated(): void
    {
        $body = str_repeat('x', 12 * 1024 * 1024 + 65537);
        $this->assertSame(413, $this->request('/api/v2/academics/content/versions/12', 'PUT', $body, 1)->getStatusCode());
        $this->assertSame(413, $this->request('/api/v2/me/profile', 'PUT', str_repeat('x', 1024 * 1024 + 1), 1)->getStatusCode());
    }
}
