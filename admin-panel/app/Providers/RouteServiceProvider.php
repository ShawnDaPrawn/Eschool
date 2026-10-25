<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * The route files are registered once, by withRouting() in bootstrap/app.php.
     * Registering them here as well loaded every route twice.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        // Abuse counters use short-lived keyed network pseudonyms, never a
        // reporter identity or raw network address associated with a report.
        $wellbeingKey = fn (Request $request) => hash_hmac('sha256', (string) $request->ip().'|'.(string) $request->route('schoolSlug'), (string) config('app.key'));
        RateLimiter::for('wellbeing-public', fn (Request $request) => Limit::perMinute(120)->by('wellbeing-public:'.$wellbeingKey($request)));
        RateLimiter::for('wellbeing-submit', fn (Request $request) => Limit::perMinute(30)->by('wellbeing-submit:'.$wellbeingKey($request)));

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Every admin panel and website request. Signed-in staff are counted per
        // account (a whole school can share one address); visitors per address.
        // Generous for normal use, but one client can't keep the server busy.
        RateLimiter::for('web', function (Request $request) {
            if ($request->is('webhook/*')) {
                return Limit::none();
            }

            return $request->user()
                ? Limit::perMinute(600)->by('web-user:'.$request->user()->id)
                : Limit::perMinute(300)->by('web-ip:'.$request->ip());
        });

        // PDFs, exports and bulk imports each take seconds of CPU and a lot of
        // memory, so one user can't run them back to back. Staff get more: printing
        // a class's report cards one by one is normal.
        RateLimiter::for('heavy', function (Request $request) {
            $key = 'heavy:'.($request->user()?->id ?: $request->ip());

            return $request->is('api/*') ? Limit::perMinute(10)->by($key) : Limit::perMinute(30)->by($key);
        });

        // Panel and app logins and password resets. Default passwords are dates of
        // birth, so guesses are limited per account (one count across the panel
        // and every app) as well as per address. Wrong passwords are also counted
        // over a longer time; see AppServiceProvider::limitFailedLogins().
        RateLimiter::for('login', function (Request $request) {
            $account = strtolower(trim((string) ($request->input('email') ?? $request->input('gr_number') ?? $request->input('gr_no') ?? '')));

            return [
                Limit::perMinute(5)->by('login:'.$account),
                Limit::perMinute(20)->by('login-ip:'.$request->ip()),
            ];
        });

        // Checking or changing the signed-in user's own password.
        RateLimiter::for('password', fn (Request $request) => Limit::perMinute(20)->by('password:'.($request->user()?->id ?: $request->ip())));

        // Content security policy reports: one page load can send a few, a flood can't fill the log.
        RateLimiter::for('csp', fn (Request $request) => Limit::perMinute(30)->by('csp:'.$request->ip()));

        $this->configureApiV2RateLimiting();
    }

    /**
     * The API v2 limiters (Bible 3.3). Every v2 route already has throttle:api
     * from the api middleware group (60 a minute per user, which also holds
     * every token of that user to 60). These are added on top:
     *
     * - auth: sign-in, one-time codes and resets, 5 a minute per account and
     *   20 a minute per address;
     * - money: money writes, 30 a minute per token (per session for the web
     *   portals, per address when signed out).
     */
    protected function configureApiV2RateLimiting(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            $account = strtolower(trim((string) ($request->input('email') ?? $request->input('handle') ?? $request->input('gr_number') ?? '')));

            return [
                // Without an account name, guesses are still counted per address.
                Limit::perMinute(5)->by('auth:'.($account !== '' ? $account : 'ip:'.$request->ip())),
                Limit::perMinute(20)->by('auth-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('money', fn (Request $request) => Limit::perMinute(30)->by('money:'.self::tokenKey($request)));
    }

    /**
     * The caller's token: its id for an app's bearer token, the user for a web
     * portal's session (Sanctum's cookie mode has no token row), the address
     * for a guest.
     */
    public static function tokenKey(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            return 'ip:'.$request->ip();
        }

        $token = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        return $token instanceof PersonalAccessToken ? 'token:'.$token->getKey() : 'user:'.$user->getAuthIdentifier();
    }
}
