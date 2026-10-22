<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ApiRoute;
use Tests\TestCase;

/**
 * The tenancy suite (Bible 5.2; Phase 0 step 13 builds the scaffold): one
 * case per API route, v1 and v2, read from the router, so a new route is
 * covered the moment it exists.
 *
 * Phase 0 has one school, so each case asserts what can be asserted now: the
 * route exists, it needs a token (a request without one gets 401) unless it
 * is on the public list below, and it sits in a role group unless it is on
 * the any-profile list.
 *
 * Phase 1 adds School B (DemoSchoolSeeder) and replaces assertIsolated() with
 * the real check: a School A user asks for a School B record through the
 * route and gets 403 or 404, never data. The cases, lists and the route
 * enumeration stay as they are.
 */
class ApiRouteTenancyTest extends TestCase
{
    /**
     * Routes anyone may call without signing in, each with its reason. Keep
     * it short: everything else needs a token.
     */
    public const PUBLIC = [
        'POST api/student/login' => 'sign-in',
        'POST api/student/forgot-password' => 'password reset before sign-in',
        'POST api/parent/login' => 'sign-in',
        'POST api/teacher/login' => 'sign-in',
        'POST api/forgot-password' => 'password reset before sign-in',
        'GET api/settings' => 'app settings and versions the apps read before sign-in (no secrets, see StudentApiSecurityTest)',
        'GET api/holidays' => 'public school calendar',
        'GET api/sliders' => 'public home-screen images',
        'GET api/current-session-year' => 'public school year',
        'GET api/get-session-year' => 'public school years',
        'GET api/get-semesters' => 'public terms',
        'GET api/get-events-list' => 'public school events',
        'GET api/get-events-details' => 'public school events',
        'POST api/get-events-details' => 'public school events (older clients)',
        'GET api/get-gallery-albums' => 'public gallery',
        'GET api/get-gallery-images' => 'public gallery',
        'GET api/get-gallery-videos' => 'public gallery',
    ];

    /**
     * Signed-in routes with no role group because every profile uses them.
     * v1 only: v2 routes for every profile sit in the role.any group.
     */
    public const ANY_PROFILE = [
        'POST api/logout' => 'every app signs out (v1)',
        'POST api/change-password' => 'every app changes its own password (v1)',
    ];

    /** @return iterable<string, array{0: string, 1: string}> */
    public static function apiRoutes(): iterable
    {
        foreach (ApiRoute::fromFreshApplication() as $route) {
            yield strtolower($route->label()) => [$route->method, $route->uri];
        }
    }

    #[DataProvider('apiRoutes')]
    public function test_route_is_guarded(string $method, string $uri): void
    {
        $route = $this->route($method, $uri);
        $label = $route->label();

        if (array_key_exists($label, self::PUBLIC)) {
            $this->assertFalse($route->requiresAuthentication(), "$label is on the public list but needs a token: take it off the list");

            return;
        }

        $this->assertTrue($route->requiresAuthentication(), "$label needs auth:sanctum, or a place on the public list with a reason");
        $this->json($method, $route->path())->assertStatus(401);

        if (array_key_exists($label, self::ANY_PROFILE)) {
            $this->assertFalse($route->hasRoleGroup(), "$label is on the any-profile list but has a role group: take it off the list");
        } else {
            $this->assertTrue($route->hasRoleGroup(), "$label is in no role group: put it in role:<Role>, staff or role.any");
        }

        $this->assertIsolated($route);
    }

    public function test_the_lists_only_name_routes_that_exist(): void
    {
        $labels = array_map(fn (ApiRoute $route) => $route->label(), ApiRoute::all($this->app['router']));

        $this->assertSame([], array_values(array_diff(array_keys(self::PUBLIC + self::ANY_PROFILE), $labels)), 'These listed routes no longer exist');
        $this->assertGreaterThan(150, count($labels), 'The router should list every v1 and v2 route');
    }

    /**
     * Phase 0: one school, nothing to cross. Phase 1 replaces this with a
     * request as a School A user for a School B record (id, child_id,
     * student_id and so on, per route) that must answer 403 or 404.
     */
    private function assertIsolated(ApiRoute $route): void
    {
        $this->assertNotSame('', $route->uri);
    }

    private function route(string $method, string $uri): ApiRoute
    {
        foreach (ApiRoute::all($this->app['router']) as $route) {
            if ($route->method === $method && $route->uri === $uri) {
                return $route;
            }
        }

        $this->fail("$method $uri is not a route");
    }
}
