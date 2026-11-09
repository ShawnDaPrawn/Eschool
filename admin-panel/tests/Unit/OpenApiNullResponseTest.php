<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\OpenApiSpec;

class OpenApiNullResponseTest extends TestCase
{
    public function test_a_null_only_payload_matches_the_contract(): void
    {
        OpenApiSpec::assertResponseMatches('POST', 'api/v2/pool/sign-out', $this->response(null));
    }

    #[DataProvider('invalidPayloads')]
    public function test_a_non_null_payload_still_breaks_the_contract(mixed $payload): void
    {
        $this->expectException(AssertionFailedError::class);
        OpenApiSpec::assertResponseMatches('POST', 'api/v2/pool/sign-out', $this->response($payload));
    }

    public static function invalidPayloads(): array
    {
        return [[[]], [(object) []], [''], [0], [false]];
    }

    private function response(mixed $payload): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'error' => false, 'message' => 'Signed out.', 'data' => $payload, 'code' => null,
        ], JSON_THROW_ON_ERROR));
    }
}
