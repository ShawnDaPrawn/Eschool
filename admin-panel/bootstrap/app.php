<?php

use App\Exceptions\AdmissionsExceptionRenderer;
use App\Exceptions\ApiV2ExceptionRenderer;
use App\Exceptions\PublicReportingExceptionRenderer;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckChild;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckStudent;
use App\Http\Middleware\DemoMiddleware;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\EndRefusedPortalSignIn;
use App\Http\Middleware\EnforcePasswordChange;
use App\Http\Middleware\EnsureDefaultSchool;
use App\Http\Middleware\EnsureLearnerApproved;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureParentChildrenSessionIsActive;
use App\Http\Middleware\EnsureStudentSessionIsActive;
use App\Http\Middleware\EnsureTeacherActive;
use App\Http\Middleware\EnsureUserHasAnyRole;
use App\Http\Middleware\EnsureUserIsStaff;
use App\Http\Middleware\HandleIdempotencyKey;
use App\Http\Middleware\LanguageManager;
use App\Http\Middleware\LimitRequestInput;
use App\Http\Middleware\ObserveLearnerActivity;
use App\Http\Middleware\PrivateDomainResponse;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RejectDangerousUploads;
use App\Http\Middleware\RequireParentPolicyAcceptance;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\RequireSchoolPushTopics;
use App\Http\Middleware\ResetSchoolContext;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetSchoolContext;
use App\Providers\ObservabilityServiceProvider;
use App\Services\Platform\Modules\ModuleRoutes;
use dacoto\LaravelWizardInstaller\LaravelWizardInstallerServiceProvider;
use dacoto\LaravelWizardInstaller\Middleware\InstallerMiddleware;
use dacoto\LaravelWizardInstaller\Middleware\ToInstallMiddleware;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\SanctumServiceProvider;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Spatie\Permission\PermissionServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // API v2: same "api" middleware group as v1, under /api/v2.
        then: function () {
            Route::middleware('api')->prefix('api/v2')->group(__DIR__.'/../routes/api_v2.php');
            Route::middleware('api')->group(__DIR__.'/../routes/internal.php');
            Route::middleware(['api', 'auth:sanctum', 'school.context', 'role.any', 'learner.approved'])
                ->prefix('protected/profile-files')->name('profile-files.')->group(__DIR__.'/../routes/profile_files.php');
            Route::group([], __DIR__.'/../routes/public_wellbeing.php');
            Route::group([], __DIR__.'/../routes/public_admissions.php');
            ModuleRoutes::apply(Route::getRoutes());
        },
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
            // Browsers send content security policy reports without a token.
            'csp-report',
        ]);

        // Every request starts with no school; school.context chooses it.
        $middleware->prepend(ResetSchoolContext::class);
        // Outermost, so every response and every log line of the request has its id.
        $middleware->prepend(AssignRequestId::class);
        // Keep private route refusals inside the response boundary, including
        // authentication failures. Subclasses such as PrivateWellbeing inherit it.
        $middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: PrivateDomainResponse::class);
        // The school is chosen right after authentication, before route model
        // binding or anything else reads school-owned rows.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: SetSchoolContext::class);
        $middleware->appendToPriorityList(after: SetSchoolContext::class, append: EnsureModuleEnabled::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->append(LimitRequestInput::class);

        // Web middleware group. The app's EncryptCookies replaces the framework's
        // (keeping both encrypted every cookie twice, so the web portals' API
        // calls, which Sanctum decrypts once with the app's, could not read the
        // session that /sanctum/csrf-cookie started: defect D1).
        $middleware->web(replace: [
            BaseEncryptCookies::class => EncryptCookies::class,
        ], append: [
            LanguageManager::class,
            // Signs out other sessions when the password changes.
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            RequirePasswordChange::class,
            // After the session starts, so signed-in users are counted per account.
            'throttle:web',
            SubstituteBindings::class,
            DemoMiddleware::class,
            RejectDangerousUploads::class,
        ]);

        // API middleware group
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            SubstituteBindings::class,
        ]);
        $middleware->api(append: [
            ObserveLearnerActivity::class,
            RejectDangerousUploads::class,
        ]);

        // Alias middleware
        $middleware->alias([
            'auth' => Authenticate::class,
            // The school a signed-in request works for (after auth, on every group).
            'school.context' => SetSchoolContext::class,
            // Pages that change what every school shares: the default school only.
            'school.default' => EnsureDefaultSchool::class,
            'Role' => CheckRole::class,
            'checkChild' => CheckChild::class,
            'checkStudent' => CheckStudent::class,
            'language' => LanguageManager::class,
            'demo' => DemoMiddleware::class,
            // Spatie Permission aliases
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,

            // 'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'student.session.active' => EnsureStudentSessionIsActive::class,
            'learner.approved' => EnsureLearnerApproved::class,
            'module' => EnsureModuleEnabled::class,
            'parent.children.session.active' => EnsureParentChildrenSessionIsActive::class,
            'parent.policy' => RequireParentPolicyAcceptance::class,
            'teacher.policy' => RequireParentPolicyAcceptance::class,
            'teacher.active' => EnsureTeacherActive::class,
            'password.change' => EnforcePasswordChange::class,
            // The app sign-in endpoints: a refused web portal sign-in keeps no session.
            'portal.sign-in' => EndRefusedPortalSignIn::class,
            // v1 app sign-in: older app versions only for the first school's people.
            'push.topics' => RequireSchoolPushTopics::class,
            // API v2 role groups and writes (routes/api_v2.php).
            'staff' => EnsureUserIsStaff::class,
            'role.any' => EnsureUserHasAnyRole::class,
            'idempotent' => HandleIdempotencyKey::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'guest' => RedirectIfAuthenticated::class,
            'password.confirm' => RequirePassword::class,
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
            'verified' => EnsureEmailIsVerified::class,
            // 'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
            // 'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
            // 'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
            'Role' => CheckRole::class,
            'checkChild' => CheckChild::class,
            // Installer middleware aliases
            'installer' => InstallerMiddleware::class,
            'to.install' => ToInstallMiddleware::class,
        ]);
    })
    ->withProviders([
        // Third-party providers
        PermissionServiceProvider::class,
        SanctumServiceProvider::class,
        LaravelWizardInstallerServiceProvider::class,
        // Queue and worker logging, and the /up/worker health route.
        ObservabilityServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        // The apps don't send Accept: application/json. Without this an expired token
        // got a redirect to the login page instead of the 401 that signs the app out.
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());
        // API v2 answers every error in the {error, message, data, code} envelope
        // with its real HTTP status (v1 keeps Laravel's own error bodies).
        $exceptions->render(fn (Throwable $e, Request $request) => AdmissionsExceptionRenderer::render($e, $request));
        $exceptions->render(fn (Throwable $e, Request $request) => PublicReportingExceptionRenderer::render($e, $request));
        $exceptions->render(fn (Throwable $e, Request $request) => ApiV2ExceptionRenderer::render($e, $request));
    })->create();
