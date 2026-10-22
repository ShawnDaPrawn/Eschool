<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Trusted proxies come from config/trustedproxy.php (all callers on Cloud
        // Run, which terminates TLS; none elsewhere unless TRUSTED_PROXIES is set).
        // Only the client address and scheme are taken from them: Cloud Run passes
        // a visitor's own X-Forwarded-Host/-Port/-Prefix through unchanged, and
        // those would change the links the app builds.
        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO);
        // Hosts are limited to APP_URL plus local development and TRUSTED_HOSTS. Until
        // the install wizard has finished, APP_URL is still .env.example's placeholder,
        // so the wizard answers on whichever address the school opens it on.
        $middleware->trustHosts(at: fn () => file_exists(storage_path('installed'))
            ? [
                '^localhost$',
                '^127\.0\.0\.1$',
                ...array_map(
                    fn (string $host) => '^'.preg_quote($host).'$',
                    array_filter(array_map('trim', explode(',', (string) config('app.trusted_hosts'))))
                ),
            ]
            : ['.*']);

        $middleware->validateCsrfTokens(except: [
            'webhook/*',
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\LimitRequestInput::class);

        // Web middleware group
        $middleware->web(append: [
            \App\Http\Middleware\LanguageManager::class,
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // Signs out other sessions when the password changes.
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\RequirePasswordChange::class,
            // After the session starts, so signed-in users are counted per account.
            'throttle:web',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\DemoMiddleware::class,
            \App\Http\Middleware\RejectDangerousUploads::class,
        ]);

        // API middleware group
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\RejectDangerousUploads::class,
        ]);

        // Alias middleware
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'Role' => \App\Http\Middleware\CheckRole::class,
            'checkChild' => \App\Http\Middleware\CheckChild::class,
            'checkStudent' => \App\Http\Middleware\CheckStudent::class,
            'language' => \App\Http\Middleware\LanguageManager::class,
            'demo' => \App\Http\Middleware\DemoMiddleware::class,
            // Spatie Permission aliases
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

            // 'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'student.session.active' => \App\Http\Middleware\EnsureStudentSessionIsActive::class,
            'parent.children.session.active' => \App\Http\Middleware\EnsureParentChildrenSessionIsActive::class,
            'parent.policy' => \App\Http\Middleware\RequireParentPolicyAcceptance::class,
            'teacher.policy' => \App\Http\Middleware\RequireParentPolicyAcceptance::class,
            'teacher.active' => \App\Http\Middleware\EnsureTeacherActive::class,
            'password.change' => \App\Http\Middleware\EnforcePasswordChange::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            // 'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
            // 'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
            // 'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
            'Role' => \App\Http\Middleware\CheckRole::class,
            'checkChild' => \App\Http\Middleware\CheckChild::class,
            // Installer middleware aliases
            'installer' => \dacoto\LaravelWizardInstaller\Middleware\InstallerMiddleware::class,
            'to.install' => \dacoto\LaravelWizardInstaller\Middleware\ToInstallMiddleware::class,
        ]);
    })
    ->withProviders([
        // Third-party providers
        \Spatie\Permission\PermissionServiceProvider::class,
        \Laravel\Sanctum\SanctumServiceProvider::class,
        \dacoto\LaravelWizardInstaller\LaravelWizardInstallerServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        // The apps don't send Accept: application/json. Without this an expired token
        // got a redirect to the login page instead of the 401 that signs the app out.
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());
    })->create();
