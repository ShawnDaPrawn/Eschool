<?php

declare(strict_types=1);

namespace Tests\Feature\ChildMode;

use App\Http\Middleware\ChildModeContext;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\GuardianService;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Events\TokenAuthenticated;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\UsesTwoSchools;
use Tests\Support\ApiRoute;
use Tests\TestCase;

/**
 * A child token (Action Plan 4.2) opens the two child mode session routes
 * and nothing else, proved over the router's whole route list (like the
 * pool member token): on every other route that takes app tokens (every
 * API route, v1 and v2, and the private profile files) it never
 * authenticates, so every route that needs a sign-in answers 401 and every
 * public route answers as it would to a visitor. The panel's routes use
 * the web session, never a bearer token.
 */
class ChildTokenRouteListTest extends TestCase
{
    use UsesTwoSchools;

    /** The only routes a child token opens. */
    private const CHILD_ROUTES = ['GET api/v2/child-mode/session', 'POST api/v2/child-mode/session/end'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2027-05-10 09:00:00', 'UTC'));
        $this->setUpTwoSchools();
        Queue::fake();
        Mail::fake();
        // One request per route: the per-minute limits would otherwise answer 429 before auth.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function childToken(): string
    {
        $farid = User::query()->findOrFail($this->schoolA->ids['farid_user']);
        $farid->forceFill(['email_verified_at' => now()])->save();
        $asha = Students::query()->findOrFail($this->schoolA->ids['asha']);
        app(GuardianService::class)->syncLegacy($asha);
        ClassSchool::query()->whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => GradeLevel::query()->where('code', '5')->value('id')]);
        app(ApprovalService::class)->decide($farid, $asha, 'approve', (string) config('identity.consent_version'));
        $parent = AppTokens::issue($farid, AppTokens::PARENT);
        $base = '/api/v2/child-mode/children/'.$asha->id;
        $this->withToken($parent)->withHeaders(['X-School-Id' => '1'])->putJson($base.'/pin', ['pin' => '2580', 'pin_confirmation' => '2580'])->assertOk();
        $this->app['auth']->forgetGuards();
        $token = $this->postJson($base.'/sessions', ['pin' => '2580'])->assertOk()->json('data.token');
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $token;
    }

    public function test_a_child_token_authenticates_on_no_route_but_its_own(): void
    {
        $child = $this->childToken();
        $childId = (int) PersonalAccessToken::findToken($child)->getKey();
        $authenticatedOn = null;
        Event::listen(TokenAuthenticated::class, function (TokenAuthenticated $event) use (&$authenticatedOn, $childId) {
            if ((int) $event->token->getKey() === $childId) {
                $authenticatedOn = true;
            }
        });

        $routes = ApiRoute::all($this->app['router']);
        $this->assertGreaterThan(300, count($routes));
        // The private profile files take app tokens too: walk them with the API.
        $outside = 0;
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            /** @var Route $route */
            $middleware = array_values(array_map('strval', $route->gatherMiddleware()));
            if (! str_starts_with($route->uri(), 'api/') && in_array('auth:sanctum', $middleware, true)) {
                foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                    $routes[] = new ApiRoute($method, $route->uri(), $middleware);
                    $outside++;
                }
            }
        }
        $this->assertGreaterThan(0, $outside);
        $own = [];
        $wronglyServed = [];
        foreach ($routes as $route) {
            if (in_array($route->label(), self::CHILD_ROUTES, true)) {
                $own[] = $route->label();
                $this->assertContains(ChildModeContext::class, $route->middleware);

                continue;
            }
            $this->assertNotContains(ChildModeContext::class, $route->middleware, $route->label().' must not accept a child token');
            $authenticatedOn = false;
            $this->app['auth']->forgetGuards();
            $path = str_starts_with($route->uri, 'protected/profile-files/') ? $route->path(['file' => '8aef50e7-2429-4d2c-a523-668ae436c4d4.jpg']) : $route->path();
            $status = $this->flushHeaders()->withToken($child)->withHeaders(['X-School-Id' => '1'])->json($route->method, $path)->status();
            if ($authenticatedOn || ($route->requiresAuthentication() && $status !== 401)) {
                $wronglyServed[] = $route->label().' answered '.$status;
            }
        }
        $this->assertSame([], $wronglyServed, 'A child token must open no other route');
        $this->assertEqualsCanonicalizing(self::CHILD_ROUTES, $own);

        // And the proof is not empty: the same token still opens its own route.
        $authenticatedOn = false;
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($child)->getJson('/api/v2/child-mode/session')->assertOk();
        $this->assertTrue($authenticatedOn);
        $this->assertSame(1, DB::table('child_mode_sessions')->whereNull('ended_at')->count());
    }
}
