<?php

declare(strict_types=1);

namespace App\Providers;

use App\Exceptions\TooManyLoginFailures;
use App\Services\FormService;
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

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('form', function ($app) {
            return new FormService();
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

        // Reset links always point at APP_URL, never at the host a request named.
        ResetPassword::createUrlUsing(fn ($user, string $token) => rtrim((string) config('app.url'), '/')
            .route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false));

        // A password reset signs the account out of the apps too.
        Event::listen(PasswordReset::class, fn (PasswordReset $event) => $event->user->tokens()->delete());

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
