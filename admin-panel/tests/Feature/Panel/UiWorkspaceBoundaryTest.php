<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnforceReadOnlyYears;
use App\Http\Middleware\SetSchoolContext;
use App\Http\Middleware\StartDocumentSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UiWorkspaceBoundaryTest extends TestCase
{
    public function test_every_added_workspace_route_inherits_the_authenticated_school_panel_boundary(): void
    {
        $prefixes = ['panel.workspace', 'academic-registers.configuration', 'facilitator-leave.', 'leave-calendar.', 'new-year.result', 'school-modules.readiness', 'school-subscription-invoices.', 'bursary-panel.', 'giving-panel.', 'sponsored-places.', 'panel.inbox.', 'panel.preferences.', 'event-readiness.'];
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            if (! collect($prefixes)->contains(fn ($prefix) => str_starts_with((string) $route->getName(), $prefix))) {
                continue;
            }
            $stack = app('router')->gatherRouteMiddleware($route);
            foreach ([Authenticate::class, CheckRole::class, SetSchoolContext::class, EnforceReadOnlyYears::class] as $required) {
                $this->assertContains($required, $stack, $route->getName().' must retain the school panel boundary.');
            }
            $this->assertNotContains(SetSchoolContext::class.':public', $stack);
            $this->assertSame(1, count(array_filter($stack, fn ($middleware) => is_a(explode(':', $middleware)[0], StartSession::class, true))));
            $this->assertContains(StartDocumentSession::class, $stack);
            $checked++;
        }
        $this->assertGreaterThan(35, $checked);
    }
}
