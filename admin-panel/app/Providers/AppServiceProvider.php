<?php

declare(strict_types=1);

namespace App\Providers;

use App\Exceptions\TooManyLoginFailures;
use App\Models\User;
use App\Services\ChildMode\ChildModeAccess;
use App\Services\FormService;
use App\Services\Passport\NoReadingRecords;
use App\Services\Passport\ReadingRecords;
use App\Services\People\Leavers\AlumniInvitations;
use App\Services\People\Leavers\NoAlumniInvitations;
use App\Services\People\Pools\BookablePeople;
use App\Services\People\Pools\SchoolPoolPeople;
use App\Services\Platform\Files\ClamAv;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\PasswordPolicy;
use App\Services\Platform\Mail\SesConfigurationSets;
use App\Services\Platform\Mail\SesIdentities;
use App\Services\SettingsConfigService;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UploadScanner::class, ClamAv::class);
        // School sending domains (Action Plan 2.2): tests bind one with a mocked client.
        $this->app->singleton(SesIdentities::class, fn () => SesIdentities::fromConfig());
        // SES streams (Action Plan 2.3): tests bind one with a mocked client.
        $this->app->singleton(SesConfigurationSets::class, fn () => SesConfigurationSets::fromConfig());
        // The alumni invitation hook (Action Plan 1.21); Phase 4 replaces it.
        $this->app->bind(AlumniInvitations::class, NoAlumniInvitations::class);
        // Who a school may book from its alumni and volunteer pool (Action Plan 5.6);
        // a cross-school source can replace it later.
        $this->app->bind(BookablePeople::class, SchoolPoolPeople::class);
        // The learner passport's reading section (Action Plan 4.10); the reading library (4.4) replaces it.
        $this->app->bindIf(ReadingRecords::class, NoReadingRecords::class);
        $this->app->singleton('form', function ($app) {
            return new FormService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for MySQL
        Schema::defaultStringLength(191);

        SettingsConfigService::apply();

        // One password rule for every password a person chooses.
        PasswordPolicy::register($this->app);

        // Reset links always point at APP_URL, never at the host a request named.
        ResetPassword::createUrlUsing(fn ($user, string $token) => rtrim((string) config('app.url'), '/')
            .route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false));

        // A password reset signs the account out of the apps too, child mode included.
        Event::listen(PasswordReset::class, fn (PasswordReset $event) => $event->user instanceof User ? AppTokens::revokeAll($event->user) : $event->user->tokens()->delete());

        // A child-mode token (Action Plan 4.2) is no token at all outside the
        // child mode routes: Sanctum asks this for every token it finds.
        Sanctum::authenticateAccessTokensUsing(fn (PersonalAccessToken $token, bool $valid): bool => $valid && ChildModeAccess::allowedHere($token));

        $this->limitFailedLogins();
    }

    /**
     * Wrong passwords are counted per account, whichever app, page or address
     * they come from, and 10 in an hour stop sign-ins to that account until the
     * hour is up. App passwords default to dates of birth, which per-minute
     * limits alone would let someone guess within a day or two.
     */
    private function limitFailedLogins(): void
    {
        $key = fn (array $credentials) => 'login-failures:'.strtolower(trim((string) ($credentials['email'] ?? '')));

        Event::listen(Attempting::class, function (Attempting $event) use ($key) {
            if (RateLimiter::tooManyAttempts($key($event->credentials), TooManyLoginFailures::MAX_FAILURES)) {
                throw new TooManyLoginFailures((string) ($event->credentials['email'] ?? ''), RateLimiter::availableIn($key($event->credentials)));
            }
        });
        Event::listen(Failed::class, fn (Failed $event) => RateLimiter::hit($key($event->credentials), 3600));
        Event::listen(Login::class, fn (Login $event) => RateLimiter::clear($key(['email' => $event->user->email ?? ''])));
    }
}
