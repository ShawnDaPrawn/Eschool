<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

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
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
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
    }
}
