<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\ApiRoute;
use Tests\TestCase;

class PrivateRouteReachabilityTest extends TestCase
{
    public function test_finite_private_dimensions_reach_authentication_before_record_lookup(): void
    {
        foreach ([
            'api/v2/family-forms/consents/{consent}',
            'api/v2/family-forms/consents/{consent}/managed',
            'api/v2/pastoral/support/configuration/{audience}',
            'api/v2/transfers/choices/{kind}',
            'api/v2/transfers/{lane}',
            'api/v2/transfers/{lane}/{record}',
            'api/v2/transfers/{lane}/{record}/archive',
        ] as $uri) {
            $route = new ApiRoute('GET', $uri, ['auth:sanctum']);
            $this->getJson($route->path())->assertUnauthorized();
        }
    }
}
