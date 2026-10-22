<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Tests\Support\OpenApiSpec;

/**
 * Every /api/v2 response a test using this trait gets is checked against
 * openapi.yaml (status, headers and body), whatever the test itself asserts.
 * A v2 request the spec does not describe fails the test.
 */
trait ValidatesOpenApiResponses
{
    protected function createTestResponse($response, $request)
    {
        $testResponse = parent::createTestResponse($response, $request);

        if ($request !== null && str_starts_with($request->path(), OpenApiSpec::BASE_PATH.'/') && ! $request->isMethod('HEAD')) {
            OpenApiSpec::assertResponseMatches($request->method(), $request->path(), $response);
        }

        return $testResponse;
    }
}
