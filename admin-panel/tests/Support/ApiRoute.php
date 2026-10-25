<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Bootstrap\SetRequestForConsole;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Facade;

/**
 * One API route and method, read from the router (never from a hand-written
 * list), for the suites that walk every route: Tenancy, Authorization and
 * Contracts.
 */
final readonly class ApiRoute
{
    /** Roles that are never staff (see EnsureUserIsStaff). */
    private const APP_ONLY_ROLES = ['Parent', 'Student'];

    /**
     * @param  list<string>  $middleware  every middleware on the route, groups and route alike
     */
    public function __construct(
        public string $method,
        public string $uri,
        public array $middleware,
    ) {}

    /**
     * Every route under /api, one entry per method (HEAD is GET's twin and
     * left out), in the router's order.
     *
     * @return list<self>
     */
    public static function all(Router $router): array
    {
        $routes = [];
        foreach ($router->getRoutes()->getRoutes() as $route) {
            /** @var Route $route */
            if (! str_starts_with($route->uri(), 'api/')) {
                continue;
            }
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $routes[] = new self($method, $route->uri(), array_values(array_map('strval', $route->gatherMiddleware())));
            }
        }

        return $routes;
    }

    /**
     * For data providers, which run before any test has an application:
     * boots one to read its routes, then lets it go. It skips the
     * HandleExceptions bootstrapper, whose global error handlers PHPUnit
     * would otherwise see a later test remove.
     *
     * @return list<self>
     */
    public static function fromFreshApplication(): array
    {
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->bootstrapWith([
            LoadEnvironmentVariables::class,
            LoadConfiguration::class,
            RegisterFacades::class,
            SetRequestForConsole::class,
            RegisterProviders::class,
            BootProviders::class,
        ]);
        $routes = self::all($app['router']);
        $app->flush();
        Facade::clearResolvedInstances();

        return $routes;
    }

    public function label(): string
    {
        return $this->method.' '.$this->uri;
    }

    public function isV2(): bool
    {
        return str_starts_with($this->uri, 'api/v2/');
    }

    /** Numeric IDs and finite route dimensions must make a reachable URL. */
    public function path(array $parameters = []): string
    {
        return '/'.preg_replace_callback('/\{([^}]+)\}/', fn ($m) => (string) ($parameters[$m[1]] ?? $this->sampleParameter($m[1])), $this->uri);
    }

    public function sampleParameter(string $name): string
    {
        if (str_starts_with($this->uri, 'api/v2/tutor/') && in_array($name, ['session', 'operation', 'photo'], true)) {
            return '8aef50e7-2429-4d2c-a523-668ae436c4d4';
        }

        return match ($name) {
            'card', 'scan', 'device' => str_starts_with($this->uri, 'api/v2/cards/') || str_starts_with($this->uri, 'api/v2/card-device/') || str_starts_with($this->uri, 'api/v2/card-readers/') ? '8aef50e7-2429-4d2c-a523-668ae436c4d4' : '1',
            'field' => 'test_field',
            'audience' => str_contains($this->uri, '/pastoral/support/configuration/') ? 'principal' : '1',
            'lane' => str_contains($this->uri, '/transfers/') ? 'outgoing' : '1',
            'photo' => str_contains($this->uri, '/gallery/') ? '1' : '8aef50e7-2429-4d2c-a523-668ae436c4d4',
            'module' => str_contains($this->uri, '/recognition/') ? 'house_points' : '1',
            'kind' => str_contains($this->uri, '/recognition/') ? 'houses' : (str_contains($this->uri, '/transfers/choices/') ? 'learners' : (str_contains($this->uri, '/academics/allocations/') ? 'homerooms' : 'memberships')),
            default => '1',
        };
    }

    public function requiresAuthentication(): bool
    {
        return in_array('auth:sanctum', $this->middleware, true);
    }

    /** Whether the route sits in a role group: role:<Role>, staff, or role.any. */
    public function hasRoleGroup(): bool
    {
        return $this->roleGroup() !== null;
    }

    /**
     * Whether the route's role group lets a user with this role (or none)
     * through. Routes without a role group let every signed-in user through.
     */
    public function allowsRole(?string $role): bool
    {
        $group = $this->roleGroup();

        return match (true) {
            $group === null => true,
            $role === null => false,
            $group === 'role.any' => true,
            $group === 'staff' => ! in_array($role, self::APP_ONLY_ROLES, true),
            default => in_array($role, self::rolesIn($group), true),
        };
    }

    /** The role group middleware, for example "role:Parent", "staff" or "role.any". */
    public function roleGroup(): ?string
    {
        foreach ($this->middleware as $middleware) {
            if (str_starts_with($middleware, 'role:') || $middleware === 'staff' || $middleware === 'role.any') {
                return $middleware;
            }
        }

        return null;
    }

    /** @return list<string> */
    private static function rolesIn(string $group): array
    {
        // role:A|B and role:A,B both name several roles.
        return array_map('trim', preg_split('/[|,]/', substr($group, strlen('role:'))) ?: []);
    }
}
