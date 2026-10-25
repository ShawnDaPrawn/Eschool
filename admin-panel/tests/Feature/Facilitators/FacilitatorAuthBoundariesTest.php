<?php

declare(strict_types=1);

namespace Tests\Feature\Facilitators;

use App\Exceptions\TooManyLoginFailures;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\PolicyAcceptance;
use App\Models\User;
use App\Services\Facilitators\FacilitatorAccess;
use App\Services\Facilitators\FacilitatorSignIn;
use App\Services\ParentPolicyService;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class FacilitatorAuthBoundariesTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-10-25T22:30:00Z'));
        School::whereKey(1)->update(['timezone' => 'Africa/Johannesburg']);
        School::whereKey(2)->update(['timezone' => 'America/Los_Angeles']);
        app(CurrentSchool::class)->set(School::findOrFail(1));
        $this->actor = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actor->tokens()->delete();
        $this->actor->forceFill(['password' => Hash::make('SyntheticStaff2026'), 'password_change_required' => false, 'handle' => 'synthetic-fac-limit'])->save();
        $this->actor->removeRole('Teacher');
        $this->actor->assignRole('Facilitator');
        SchoolMembership::where('school_id', 1)->where('user_id', $this->actor->id)->where('role', 'Facilitator')->update(['starts_on' => '2026-01-01']);
        ParentPolicyService::acceptCurrent($this->actor);
        RateLimiter::clear($this->key());
    }

    protected function tearDown(): void
    {
        if (isset($this->actor)) {
            RateLimiter::clear($this->key());
        }
        parent::tearDown();
    }

    private function key(): string
    {
        return 'login-failures:'.strtolower(trim($this->actor->email));
    }

    private function input(array $extra = []): array
    {
        return $extra + ['school' => School::findOrFail(1)->slug, 'email' => $this->actor->email, 'password' => 'SyntheticStaff2026'];
    }

    public static function calendars(): array
    {
        return [
            'other school starts tomorrow' => ['Facilitator', '2026-10-26', null, 'active', false],
            'other school still active today' => ['Facilitator', '2026-01-01', '2026-10-25', 'active', true],
            'parent membership is not facilitator access' => ['Parent', '2026-01-01', null, 'active', false],
            'ended membership remains unavailable' => ['Facilitator', '2026-01-01', null, 'ended', false],
            'inactive school remains unavailable' => ['Facilitator', '2026-01-01', null, 'suspended_school', false],
        ];
    }

    #[DataProvider('calendars')]
    public function test_school_choices_use_each_target_school_calendar_and_retain_role_status_filters(string $role, string $starts, ?string $ends, string $status, bool $visible): void
    {
        app(CurrentSchool::class)->run(2, function () use ($role, $starts, $ends, $status) {
            $this->actor->assignRole($role);
            SchoolMembership::where('school_id', 2)->where('user_id', $this->actor->id)->where('role', $role)->update(['starts_on' => $starts, 'ends_on' => $ends, 'status' => $status === 'ended' ? 'ended' : 'active']);
            if ($status === 'suspended_school') {
                School::whereKey(2)->update(['status' => 'suspended']);
            }
        });
        app(MembershipService::class)->forget($this->actor);
        $choices = app(FacilitatorAccess::class)->profile($this->actor)['schools'];
        $this->assertSame($visible, in_array(2, array_column($choices, 'id'), true));
        $this->assertContains(1, array_column($choices, 'id'));
    }

    public function test_facilitator_alias_failures_count_towards_the_existing_accountwide_guard_limit(): void
    {
        for ($attempt = 0; $attempt < TooManyLoginFailures::MAX_FAILURES; $attempt++) {
            $input = $this->input(['email' => $attempt % 2 === 0 ? strtoupper($this->actor->email) : $this->actor->handle, 'password' => 'Synthetic-wrong-password']);
            try {
                app(FacilitatorSignIn::class)->signIn(Request::create('/api/v2/facilitator/sign-in', 'POST'), $input);
                $this->fail('A wrong facilitator password must be refused.');
            } catch (HttpExceptionInterface $error) {
                $this->assertSame(401, $error->getStatusCode());
            }
        }
        $this->assertSame(TooManyLoginFailures::MAX_FAILURES, RateLimiter::attempts($this->key()));
        try {
            Auth::guard('web')->attempt(['email' => $this->actor->email, 'password' => 'SyntheticStaff2026']);
            $this->fail('The legacy guard must honor failures made through facilitator sign-in.');
        } catch (TooManyLoginFailures $error) {
            $this->assertGreaterThan(3000, $error->seconds);
        }
        $this->assertGuest();
        $this->assertSame(0, $this->actor->tokens()->count());
    }

    public function test_legacy_failures_block_a_correct_facilitator_password_with_a_private_typed_429(): void
    {
        for ($attempt = 0; $attempt < TooManyLoginFailures::MAX_FAILURES; $attempt++) {
            $this->assertFalse(Auth::guard('web')->attempt(['email' => $this->actor->email, 'password' => 'Synthetic-wrong-password']));
        }
        $this->app['auth']->forgetGuards();
        $response = $this->postJson('/api/v2/facilitator/sign-in', $this->input())->assertTooManyRequests()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertGreaterThan(3000, (int) $response->headers->get('Retry-After'));
        $this->assertSame(TooManyLoginFailures::MAX_FAILURES, RateLimiter::attempts($this->key()));
        $this->assertGuest();
        $this->assertSame(0, $this->actor->tokens()->count());
    }

    public function test_only_completed_bearer_signin_clears_failures_and_emits_one_success_event(): void
    {
        for ($attempt = 0; $attempt < 9; $attempt++) {
            $this->assertFalse(Auth::guard('web')->attempt(['email' => $this->actor->email, 'password' => 'Synthetic-wrong-password']));
        }
        PolicyAcceptance::where('user_id', $this->actor->id)->delete();
        $successes = [];
        Event::listen(Login::class, function (Login $event) use (&$successes) {
            $successes[] = (int) $event->user->id;
        });
        $this->postJson('/api/v2/facilitator/sign-in', $this->input())->assertConflict()->assertJsonPath('data.token', null);
        $this->assertSame(9, RateLimiter::attempts($this->key()));
        $this->assertSame([], $successes);
        $this->assertGuest();
        $this->assertSame(0, $this->actor->tokens()->count());
        ParentPolicyService::acceptCurrent($this->actor);
        $this->postJson('/api/v2/facilitator/sign-in', $this->input())->assertOk()->assertJsonPath('data.authenticated', true);
        $this->assertSame(0, RateLimiter::attempts($this->key()));
        $this->assertSame([$this->actor->id], $successes);
        $this->assertSame(1, $this->actor->tokens()->count());
    }

    public function test_existing_per_minute_route_limit_and_shared_account_key_remain(): void
    {
        $route = Route::getRoutes()->match(Request::create('/api/v2/facilitator/sign-in', 'POST'));
        $this->assertContains('throttle:login', $route->gatherMiddleware());
        $limiter = RateLimiter::limiter('login');
        $this->assertSame($limiter(Request::create('/login', 'POST', ['email' => $this->actor->email]))[0]->key,
            $limiter(Request::create('/api/v2/facilitator/sign-in', 'POST', ['email' => strtoupper($this->actor->email)]))[0]->key);
    }
}
