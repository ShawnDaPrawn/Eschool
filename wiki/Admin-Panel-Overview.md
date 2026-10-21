This page explains how the Laravel application in `admin-panel/` is put together: how a request travels through `bootstrap/app.php` and the middleware stack, what lives in each `app/` folder, how the Blade views and static assets are organised, the global helper functions, the JSON response conventions and the main Composer packages. The same application serves the staff admin panel, the public school website and the REST API used by the mobile apps.

## At a glance

| Item | Value |
|---|---|
| Framework | Laravel 12 (`laravel/framework` locked at v12.70.0 in `composer.lock`) |
| PHP | `^8.3.0` (`composer.json` pins the platform to 8.3.0 and sets `platform-check: false`) |
| Database | MySQL or MariaDB (the migrations use MySQL-specific SQL; CI runs MariaDB 10.11) |
| UI | Server-rendered Blade, Bootstrap, jQuery, bootstrap-table; prebuilt files in `public/assets/` |
| Auth | Session guard `web` for the panel; Laravel Sanctum bearer tokens (guard `sanctum`) for the apps |
| Authorisation | `spatie/laravel-permission` roles and permissions (see [Roles-and-Permissions](Roles-and-Permissions)) |
| Entry points | `public/index.php` (HTTP), `artisan` (CLI) |
| Route files | `routes/web.php` (admin panel, public site, webhooks, maintenance URLs), `routes/api.php` (mobile apps, prefix `/api`), `routes/console.php` (closure commands and the schedule) |
| Health check | `GET /up` (registered by `withRouting(health: '/up')`, outside the `web` group) |
| Size | 46 controller files in `app/Http/Controllers` plus 4 API and 6 auth controllers, 76 models, 91 migrations, 668 registered routes |

## Request lifecycle

There is no `app/Http/Kernel.php`. The app uses the Laravel 11+ style [bootstrap/app.php](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/bootstrap/app.php), which configures routing, middleware and providers in one place.

```mermaid
flowchart TD
    A["public/index.php"] --> B["bootstrap/app.php (Application::configure)"]
    B --> C["Global middleware: framework defaults + SecurityHeaders"]
    C --> D{"Route group"}
    D -->|"routes/web.php"| E["web group: ToInstallMiddleware, cookies, session, CSRF, LanguageManager, DemoMiddleware, RejectDangerousUploads"]
    D -->|"routes/api.php (/api)"| F["api group: Sanctum stateful check, throttle:api, RejectDangerousUploads"]
    D -->|"/up"| G["Health check view"]
    E --> H["Route middleware: Role, auth, language, role:Super Admin"]
    F --> I["Route middleware: auth:sanctum, role:Student / Parent / Teacher, session checks, checkChild"]
    H --> J["Controller: permission check with Auth::user()->can(...)"]
    I --> J
    J --> K["View, redirect or JSON"]
```

1. `public/index.php` checks for `storage/framework/maintenance.php` (set by `php artisan down`), loads Composer and boots the app from `bootstrap/app.php`.
2. Service providers register. They come from three places:
   - the `providers` array in [config/app.php](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/config/app.php), which Laravel 12 still honours. It lists the framework providers, `Barryvdh\DomPDF\ServiceProvider`, `Barryvdh\Debugbar\ServiceProvider`, Sanctum, Spatie Permission, `Maatwebsite\Excel\ExcelServiceProvider`, and the app's `AppServiceProvider`, `AuthServiceProvider`, `EventServiceProvider` and `RouteServiceProvider` (`BroadcastServiceProvider` is commented out);
   - `->withProviders([...])` in `bootstrap/app.php` (Spatie Permission, Sanctum and the wizard installer, again);
   - package auto-discovery (for example `Unicodeveloper\Paystack\PaystackServiceProvider`, `Laravel\Ui\UiServiceProvider`, `dacoto\EnvSet\EnvSetServiceProvider`).
3. The installer's service provider prepends `ToInstallMiddleware` to the `web` group. Until `storage/installed` exists, every web route except `install/*` redirects to the wizard (see [Installation-Wizard](Installation-Wizard)).
4. Global middleware runs, then the route's group (`web` or `api`), then route middleware.
5. Controllers do most authorisation themselves: almost every action starts with `if (! Auth::user()->can('some-permission'))` and returns a redirect or JSON error.
6. The response is a Blade view, a redirect with flashed `success`/errors, or JSON. API controllers send JSON through `ResponseService`, which calls `exit()` (see [ResponseService conventions](#responseservice-conventions)).

`App\Providers\RouteServiceProvider` is still registered through `config/app.php`. It defines the rate limiters and `HOME = '/home'`, and its `boot()` also calls `$this->routes(...)` for `routes/api.php` and `routes/web.php`, so the route files are loaded more than once. Later definitions overwrite earlier ones with the same method and URI, so the result is the same route table.

Rate limiters defined in [RouteServiceProvider.php](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Providers/RouteServiceProvider.php):

| Limiter | Limits | Used by |
|---|---|---|
| `api` | 60 per minute per user id, or per IP when signed out | the `api` middleware group |
| `login` | 5 per minute per path and account (`email`, `gr_number` or `gr_no` input), and 20 per minute per IP | app logins, `forgot-password` endpoints, `POST password/email` |

The public forms use inline limits: `POST contact-us/store` has `throttle:5,1` and `POST student-register` has `throttle:10,1`.

## bootstrap/app.php

```php
->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
)
```

What the `withMiddleware` callback does:

| Call | Effect |
|---|---|
| `validateCsrfTokens(except: ['webhook/*'])` | Payment gateway webhooks (`POST webhook/razorpay`, `stripe`, `paystack`, `flutterwave`) skip CSRF. |
| `append(SecurityHeaders::class)` | Adds `SecurityHeaders` to the end of the global stack. |
| `web(append: [...])` | Appends `LanguageManager`, `App\Http\Middleware\EncryptCookies`, `AddQueuedCookiesToResponse`, `StartSession`, `ShareErrorsFromSession`, `SubstituteBindings`, `DemoMiddleware`, `RejectDangerousUploads`. Entries already in the default group are de-duplicated. |
| `api(prepend: [...])` | Prepends Sanctum's `EnsureFrontendRequestsAreStateful`, `throttle:api` and `SubstituteBindings`. |
| `api(append: [...])` | Appends `RejectDangerousUploads`. |
| `alias([...])` | Route middleware aliases (table below). |

`->withExceptions()` is empty, so exceptions use Laravel's default handler. `app/Exceptions/Handler.php` is not referenced anywhere.

The resolved stacks (printed from the booted app):

| Stack | Order |
|---|---|
| Global | `ValidatePathEncoding`, `InvokeDeferredCallbacks`, `Illuminate\Http\Middleware\TrustProxies`, `HandleCors`, `PreventRequestsDuringMaintenance`, `ValidatePostSize`, `TrimStrings`, `ConvertEmptyStringsToNull`, `App\Http\Middleware\SecurityHeaders` (plus Debugbar's `InjectDebugbar` when the dev package is installed) |
| `web` | `ToInstallMiddleware`, `Illuminate\Cookie\Middleware\EncryptCookies`, `AddQueuedCookiesToResponse`, `StartSession`, `ShareErrorsFromSession`, `ValidateCsrfToken`, `SubstituteBindings`, `LanguageManager`, `App\Http\Middleware\EncryptCookies`, `DemoMiddleware`, `RejectDangerousUploads` |
| `api` | `EnsureFrontendRequestsAreStateful`, `throttle:api`, `SubstituteBindings`, `RejectDangerousUploads` |
| `installer` | `InstallerMiddleware` (pushed by the installer package) |

The framework's own `EncryptCookies` is already in the default `web` group, so appending the app's subclass (which has an empty `$except`) adds a second encryption layer. It is redundant, not harmful.

### Route middleware aliases

| Alias | Class |
|---|---|
| `auth` | `App\Http\Middleware\Authenticate` |
| `Role` | `App\Http\Middleware\CheckRole` |
| `checkChild` | `App\Http\Middleware\CheckChild` |
| `checkStudent` | `App\Http\Middleware\CheckStudent` |
| `language` | `App\Http\Middleware\LanguageManager` |
| `demo` | `App\Http\Middleware\DemoMiddleware` |
| `permission` | `Spatie\Permission\Middleware\PermissionMiddleware` |
| `role` | `Spatie\Permission\Middleware\RoleMiddleware` |
| `role_or_permission` | `Spatie\Permission\Middleware\RoleOrPermissionMiddleware` |
| `auth.basic` | `Illuminate\Auth\Middleware\AuthenticateWithBasicAuth` |
| `student.session.active` | `App\Http\Middleware\EnsureStudentSessionIsActive` |
| `parent.children.session.active` | `App\Http\Middleware\EnsureParentChildrenSessionIsActive` |
| `cache.headers` | `Illuminate\Http\Middleware\SetCacheHeaders` |
| `can` | `Illuminate\Auth\Middleware\Authorize` |
| `guest` | `App\Http\Middleware\RedirectIfAuthenticated` |
| `password.confirm` | `Illuminate\Auth\Middleware\RequirePassword` |
| `signed` | `Illuminate\Routing\Middleware\ValidateSignature` (the framework class, not the app's) |
| `throttle` | `Illuminate\Routing\Middleware\ThrottleRequests` |
| `verified` | `Illuminate\Auth\Middleware\EnsureEmailIsVerified` |
| `installer` | `dacoto\LaravelWizardInstaller\Middleware\InstallerMiddleware` |
| `to.install` | `dacoto\LaravelWizardInstaller\Middleware\ToInstallMiddleware` |

`role` (lower case, Spatie) and `Role` (upper case, `CheckRole`) are different middleware.

## Middleware reference

Every class in [app/Http/Middleware](https://github.com/ShawnDaPrawn/Eschool/tree/main/admin-panel/app/Http/Middleware):

| Class | Registered as | Purpose |
|---|---|---|
| `Authenticate` | alias `auth` | Redirects to `route('login')` unless the request expects JSON (then a 401). Used as `auth` on admin routes and `auth:sanctum` on API routes. |
| `CheckChild` | alias `checkChild`; also referenced by class in `routes/api.php` | Parent API: reads `child_id`, looks it up among `$request->user()->parent->children()`, stores it as the request attribute `child`. Otherwise returns `{"error":true,"message":"Invalid Child ID Passed.","code":105}`. |
| `CheckRole` | alias `Role` | Admin panel gate. Lets a signed-in user through only if they have a role other than `Parent` or `Student`; otherwise logs them out and redirects to login with `no_permission_message`. For a guest it renders `auth.login` directly. |
| `CheckStudent` | alias `checkStudent`; referenced by class in the teacher API group | Teacher API: the `student_id` must belong to a class section where the signed-in teacher is a class teacher (`class_teachers.class_teacher_id`), else error code 105. |
| `DemoMiddleware` | appended to `web`; alias `demo` | When `config('app.demo_mode')` (`DEMO_MODE`) is true, blocks every non-GET request from a signed-in user except a fixed list of URIs (logins, ID cards, certificates, chat and leave list endpoints) unless the user's email is `demomodeoff@gmail.com`. Returns error code 112. |
| `EncryptCookies` | appended to `web`; `config/sanctum.php` `encrypt_cookies` | Subclass of the framework class with an empty `$except`. |
| `EnsureParentChildrenSessionIsActive` | alias `parent.children.session.active` | Parent API: 403 if the user has no parent record; 401 `SESSION_NOT_ACTIVE` if no child has a `student_sessions` row for the current `session_year` setting; 401 `ACCOUNT_DEACTIVATED` if none of those rows has `status = 1`. |
| `EnsureStudentSessionIsActive` | alias `student.session.active` | Student API: 403 unless the user has the `Student` role, `status = 1` and a student record; 401 `SESSION_NOT_ACTIVE` if there is no `student_sessions` row for the current session year or its status is 0. |
| `LanguageManager` | appended to `web`; alias `language` | Calls `app()->setLocale(Session::get('locale'))` when the session has a locale. |
| `PreventRequestsDuringMaintenance` | not registered | The framework's class is in the global stack instead. |
| `RedirectIfAuthenticated` | alias `guest` | Redirects signed-in users to `RouteServiceProvider::HOME` (`/home`). |
| `RejectDangerousUploads` | appended to `web` and `api` | Refuses any uploaded file whose name contains a blocked extension in any dot-separated part (PHP variants, `phtml`, `phar`, `html`, `js`, `xml`, `cgi`, `htaccess`, `ini`, `exe` and others in `BLOCKED_EXTENSIONS`) or whose detected MIME type looks like PHP, HTML, JavaScript, shell, Perl, Python, Ruby, executables or XML. SVG uploads are rewritten in place by `enshrined/svg-sanitize` with remote references removed; an SVG that can't be parsed is refused. Responds with 422 JSON (`code` 102) for API, JSON or AJAX requests, otherwise redirects back with an error. |
| `SecurityHeaders` | appended to the global stack | Sets `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, and `Strict-Transport-Security: max-age=31536000` when `$request->isSecure()`. Headers already on the response are left alone. |
| `TrimStrings` | not registered | The framework's `TrimStrings` runs globally instead. |
| `TrustHosts` | not registered | Host checking is off (the framework adds `TrustHosts` only when `$middleware->trustHosts()` is called). |
| `TrustProxies` | not registered | The framework's `TrustProxies` runs globally and trusts no proxy by default. See [Deployment](Deployment#https-proxies-and-hosts). |
| `ValidateSignature` | not registered | The `signed` alias points at the framework class. |
| `VerifyCsrfToken` | only in `config/sanctum.php` (`verify_csrf_token`) | CSRF for web routes is the framework's `ValidateCsrfToken`, configured by `validateCsrfTokens(except: ['webhook/*'])`. |

Route-level usage, in short (details in [Security-Model](Security-Model) and [API-Overview](API-Overview)):

- Admin panel: `Route::group(['middleware' => ['Role', 'auth']])` with a nested `language` group. `Role` runs before `auth`.
- Maintenance URLs: `/clear` needs `auth`; `/storage-link`, `/migrate` and `/seeder_install` need `auth` and `role:Super Admin`.
- Student API: `auth:sanctum`, `role:Student`, `student.session.active`. Parent API: `auth:sanctum`, `role:Parent`, `parent.children.session.active`, with `CheckChild` on child-specific routes. Teacher API: `auth:sanctum`, `role:Teacher`, with `CheckStudent` on student-specific routes.

## The app/ folders

| Folder | Contents |
|---|---|
| `app/Http/Controllers` | Controllers for the admin panel and public site, one per module (the larger modules are split into one controller per area in a sub-folder, like `Student/`; others are `FeesTypeController`, `ExamController`, `SettingController`, `SystemUpdateController`, `WebController` for the public website, `WebhookController` for payment gateways, and so on). The base `Controller` also serves `GET /common/js/lang`, which returns `window.languageLabels = {...}` from `resources/lang/<code>.json` (falling back to `en.json`). See [Admin-Modules](Admin-Modules). |
| `app/Http/Controllers/Api` | `ApiController` (shared endpoints: settings, holidays, sliders, session years, events, gallery, forgot/change password, logout), the `Student/`, `Parent/` and `Teacher/` controllers (one per area, each role sharing an access trait). See [API-Overview](API-Overview). |
| `app/Http/Controllers/Auth` | laravel/ui controllers using the framework's `AuthenticatesUsers` and related traits. `LoginController::authenticated()` signs out users with the `Parent` or `Student` role, because those roles may only use the apps. There is no register route. |
| `app/Http/Middleware` | See the table above. |
| `app/Http/Requests` | Form requests: `StoreLessonRequest`, `UpdateLessonRequest`, `StoreLessonTopicRequest`, `UpdateLessonTopicRequest`, `StoreSessionYearRequest`. Most other validation is inline with `$request->validate()` or `Validator::make()`. |
| `app/Http/Resources` | `UserResource`, `TimetableCollection`. |
| `app/Models` | 76 Eloquent models. Some names differ from Laravel conventions: `Students`, `Parents`, `Mediums`, `Settings`, `EmailTempalate` (table `email_tempalates`). `User` uses Spatie's `HasRoles`. See [Database-Schema](Database-Schema). |
| `app/Services` | Domain services: `AcademicCalendarService` (calendar PDF), `AssignmentReportService`, `ExamReportService`, `FeesReportService`, `LeaveReportService` (student report statistics), `FeesCollectionService` (fee summary for the dashboard), `ClassSessionConfigService`, `SessionYearService` (creating session years, installments, semesters and transfers), `ChatMessageCleanupService`, `SettingsConfigService` (applies admin settings over the `.env` config on boot; see [Configuration](Configuration#settings-that-override-env)), `EmailTemplateService` (fills `{$placeholders}` with escaped values, no Blade compilation), `MailService`, `IdCardService` (student and staff ID card PDFs), `GalleryService`, `ImageService` and `UploadService` (file storage on the public disk, JPEG/PNG recompressed with Intervention Image), `OnlineExamScoringService`, `FormService` (backs the `Form` facade), `BootstrapTableService`, `ResponseService`. |
| `app/Services/Payment` | `PaymentInterface`; `RazorpayPayment`, `StripePayment`, `PaystackPayment`, `FlutterwavePayment`; `PaymentService` (factory `create($payment_method)` with constants `RAZORPAY = 1`, `STRIPE = 2`, `PAYSTACK = 3`, `FLUTTERWAVE = 4`, plus `currencyCode()`, `webhookSecret()`, `amountMatches()`); `FeesPaymentService` (server-side fee calculation and transaction state). See [Payments](Payments). |
| `app/Helpers` | `settings_helper.php` and `notification_helper.php`, autoloaded as files by Composer (see [Helpers](#helpers)). |
| `app/Rules` | `AfterNow`, `YouTubeUrl`, `uniqueLessonInClass`, `uniqueTopicInLesson`. |
| `app/Imports` | Maatwebsite Excel imports: `StudentsImport` (bulk student upload, which also emails parent credentials), `AttendanceImport`, `OnlineExamQuestionsImport`. |
| `app/Exports` | `StudentsExport`. |
| `app/Actions` | `ResetLessonSemesters` (run when the default session year changes in General Settings) and `Actions/Transfers/*` (`TransferClassFeeType`, `TransferClassSessionConfig`, `TransferClassSubject`, `TransferClassTeacherAndSubjectTeacher`, `TransferClassTimetable`, `TransferExamGrades`, `TransferLeaveSettings`), used by `SessionYearService` to copy setup data into a new session year. See [Academic-Structure](Academic-Structure). |
| `app/Facades` | `Form`, resolving the `form` singleton (`FormService`) registered in `AppServiceProvider`; aliased as `Form` in `config/app.php`. |
| `app/Providers` | `AppServiceProvider` (binds `form`, sets `Schema::defaultStringLength(191)`, calls `SettingsConfigService::apply()`), `AuthServiceProvider` (no policies; the Super Admin `Gate::before` is commented out), `EventServiceProvider` (`Registered` to `SendEmailVerificationNotification`), `RouteServiceProvider` (rate limiters), `BroadcastServiceProvider` (not registered; `routes/channels.php` does not exist). |
| `app/Exceptions` | `Handler.php`, a leftover that nothing uses. |
| `app/Console` | `Commands/CleanupOldChatMessages.php`, `Commands/DeleteFeesPaid.php`, and an unused `Kernel.php`. See [Scheduled-Tasks-and-Commands](Scheduled-Tasks-and-Commands). |

## Views and layouts

Blade templates live in [resources/views](https://github.com/ShawnDaPrawn/Eschool/tree/main/admin-panel/resources/views), one folder per module (`students`, `fees`, `exams`, `online_exam`, `settings`, `system-update`, `web` and so on).

| Layout | Used by | Notes |
|---|---|---|
| `layouts/master.blade.php` | 108 admin views | Includes `layouts.include` (CSS, theme colours, early scripts), `layouts.header`, `layouts.sidebar`, `layouts.footer`, `layouts.footer_js`, then `after-update-note-modal`. Sections: `title`, `css`, `content`, `js`, `script`. |
| `web/master.blade.php` | 7 public website views | Reads `web_settings` rows (`about_us`, `who_we_are`, `teacher`, `photos`, `videos`, `question`, `registration`) to decide which sections show. See [Public-Website](Public-Website). |
| `errors::minimal` | `resources/views/errors/*` | Error pages 401 to 503. |

Points worth knowing:

- RTL: the layouts read `Session::get('language')`; when its `is_rtl` is set they add `dir="rtl"` and load `assets/css/rtl.css` instead of `style.css`.
- Theme: `layouts/include.blade.php` writes the `theme_color` and `secondary_color` settings into CSS variables `--theme-color` and `--secondary-color`, and the `login_image` setting into `--image-url`.
- Translations for JavaScript come from `<script src="{{ route('common.language.read') }}">`, which defines `window.languageLabels`. See [Localization](Localization).
- Logos and favicon come from the `logo1`, `logo2` and `favicon` settings through `schoolLogoUrl()` and `schoolSetting()`; the school name in titles and footers is `config('app.name')`, which the `school_name` setting overrides. No view calls `env()` (enforced by `SettingsConfigTest`).
- `resources/views/components`: `x-icon` (inline Lucide SVG icons by name), `form/image-upload`, `form/status-radio`, `wizard`, `web-settings/section`.

### Data tables (bootstrap-table)

List pages use bootstrap-table with server-side pagination. A typical table tag:

```html
<table data-toggle="table" data-url="{{ url('medium_list') }}"
       data-side-pagination="server" data-pagination="true"
       data-query-params="queryParams" data-escape="true">
```

The list endpoint (for example `MediumController::show`) reads `offset`, `limit`, `sort`, `order` and `search` from the query string and returns:

```json
{ "total": 12, "rows": [ { "id": 1, "no": 1, "name": "...", "operate": "<a ...>...</a>" } ] }
```

`operate` holds the row's action buttons as HTML. `data-escape="true"` appears 155 times across the views (on tables and individual columns), and the shared `escapeHtml()`/`safeUrl()` helpers in `public/assets/js/custom/function.js` are used by formatters that build HTML from row data.

[BootstrapTableService](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/BootstrapTableService.php) provides `button()`, `editButton()`, `deleteButton()`, `restoreButton()`, `trashButton()`, `viewRelatedDataButton()`, the `menu*` dropdown variants and `menuItem()`, rendering icons through `<x-icon>` with a per-request cache. No controller currently calls it; the controllers build their `operate` HTML inline.

### Front-end JavaScript conventions

Forms submit over AJAX with `formAjaxRequest()` (in `function.js`), which validates the form with jQuery Validate, disables the submit button, expects JSON `{ "error": bool, "message": string }`, and shows a toast with the message. Handlers per form live in `public/assets/js/custom/custom.js`. Script and stylesheet tags for files that change often carry `?v={{ filemtime(...) }}` as a cache buster.

### Static assets

Everything the admin panel and public site load is committed under [public/assets](https://github.com/ShawnDaPrawn/Eschool/tree/main/admin-panel/public/assets) (about 17 MB):

| Folder | Contents |
|---|---|
| `assets/css` | `style.css`, `rtl.css`, `vendor.bundle.base.css`, page styles (`sidebar.css`, `header.css`, `content.css`, `tables.css`, `timetable.css`, `system-update.css`, `webstyle.css` for the public site, and others) |
| `assets/js` | `vendor.bundle.base.js` (jQuery and Bootstrap), Chart.js, jQuery Validate, Flatpickr, Moment, SweetAlert2, jquery.repeater and others |
| `assets/js/custom` | App code: `custom.js`, `function.js`, `formatter.js`, `queryParams.js`, `custom-bootstrap-table.js`, `bootstrap-table-locale.js`, `validate.js`, `sidebar.js`, `dashboard.js`, `timetable.js`, `staff-attendance.js`, `reset-password.js`, `system-update.js` |
| `assets/bootstrap-table` | bootstrap-table with the mobile, export, fixed-columns and reorder-rows extensions, tableExport and jsPDF |
| `assets/jodit`, `assets/tinymce` | Rich text editors (Jodit replaced CKEditor 4 in the online exam editors) |
| `assets/katex` | Maths rendering |
| `assets/select2`, `assets/jquery-toast-plugin`, `assets/color-picker`, `assets/apexcharts`, `assets/fonts` | Other libraries |

One script is loaded from a CDN: the jQuery Validate message localisation from `cdnjs.cloudflare.com`.

## Front-end build (Vite)

The Vite toolchain is not used by the running application:

- [package.json](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/package.json) and [vite.config.js](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/vite.config.js) are the Laravel 12 skeleton defaults: Vite 6, `laravel-vite-plugin` and Tailwind 4, compiling `resources/css/app.css` and `resources/js/app.js` (which only sets up axios). No Blade view contains `@vite`, and there is no `public/build`.
- The Composer `dev` script (`composer run dev`) starts `php artisan serve`, `queue:listen`, `pail` and `npm run dev` together; the Vite server it starts serves nothing the pages use.

So `npm install` is not needed to run or deploy the admin panel. To change the UI, edit the files in `public/assets/` directly. The wizard installer has its own Tailwind build (`packages/laravel-wizard-installer/public/styles.css`, published to `public/vendor/installer/styles.css`).

## Helpers

Both files are autoloaded through `autoload.files` in `composer.json`, so the functions are global.

### settings_helper.php

[app/Helpers/settings_helper.php](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Helpers/settings_helper.php):

| Function | What it does |
|---|---|
| `getSettings($type = '')` | Returns an array `[type => message]` from the `settings` table: every row when `$type` is empty, otherwise only rows of that type. Always queries the database (there is no caching). Callers index the result, for example `getSettings('session_year')['session_year']`. |
| `schoolSetting(string $type, $default = null)` | One setting's value, or `$default` when the row is missing or empty. The replacement for the old `env('LOGO1')`-style reads. |
| `schoolLogoUrl(string $type = 'logo1')` | Full URL of the uploaded `logo1` or `logo2` (`url(Storage::url($path))`), or `url('assets/logo.svg')` when none is set. |
| `get_language()` | `Language::get()`, all rows of `languages`. |
| `getTimeFormat()` | The three time formats offered in General Settings (`h:i a`, `h:i A`, `H:i`) with a sample of the current time. |
| `getDateFormat()` | The twelve date formats offered in General Settings with a sample of today's date. |
| `getTimezoneList()` | Every PHP timezone identifier with its current time and UTC offset, sorted by offset. Memoised in a static variable. |
| `formatOffset($offset)` | Formats seconds as `+HH:MM` or `-HH:MM`. |
| `flattenMyModel($model)` | Flattens `$model->toArray()` into one level with `array_walk_recursive` (nested keys overwrite outer ones). |
| `findExamGrade($session_year_id, $percentage)` | Finds the `grades` row for the session year whose `starting_range`..`ending_range` contains `floor($percentage)`. Returns `''` when the session year has no grades, and `null` when none matches. |
| `resizeImage($image)` | Re-encodes an uploaded image in place with Intervention Image (GD driver) at quality 50. |
| `convertDateFormat($dateString, $format = 'Y-m-d H:i:s')` | Reformats a date string; returns the input unchanged if it is empty or can't be parsed. |
| `safe_htmlspecialchars($string, ...)` | `htmlspecialchars` that first removes control characters and NUL bytes, with a fallback that strips non-ASCII bytes if encoding fails. |
| `safe_htmlspecialchars_decode($string, ...)` | The matching decoder. |

### notification_helper.php

[app/Helpers/notification_helper.php](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Helpers/notification_helper.php) sends Firebase Cloud Messaging (HTTP v1) pushes. See [Notifications](Notifications) for where they are triggered.

| Function | What it does |
|---|---|
| `sendSimpleNotification($user, $title, $body, $type, $image, $userinfo)` | Sends to the FCM tokens (`users.fcm_id`) of the given user ids, one request per token. Android tokens get a data-only message; iOS tokens get a `notification` block plus the same data. The data always holds `click_action: FLUTTER_NOTIFICATION_CLICK`, `title`, `body`, `type`, `image`; type `chat` adds `sender_info` (JSON of `$userinfo`) and type `fees-due` adds `child_id`. |
| `sendNotificationToTopic($title, $body, $type, $image, $userinfo)` | Sends to FCM topics. `$userinfo` picks the topic pair: `all` (`allAndroid`, `allIOS`), `students`, `parents` or `teachers`. Logs and returns if there is no access token. |
| `sendFCMCurl($url, $access_token, $Data)` | POSTs one message to `https://fcm.googleapis.com/v1/projects/{project_id}/messages:send` with cURL and normal TLS verification. Returns `false` and logs on failure. |
| `getAccessToken()` | Reads the `service_account_file` setting, looks for the file on the private `local` disk first (`storage/app/firebase/service_account.json` for new uploads) and falls back to the public disk for older installs, then gets an OAuth token with scope `https://www.googleapis.com/auth/firebase.messaging` through `Google\Client`. Returns `null` and logs on any problem. |

The project id comes from the `project_id` setting. Pushes are sent synchronously during the request.

## ResponseService conventions

[app/Services/ResponseService.php](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/ResponseService.php) is used by the API controllers and by `ClassSchoolController`, `ExamController`, the `Student\` controllers, `SubjectController`, `StaffController`, `HomeController` and `OnlineExamQuestionController`.

| Method | Output |
|---|---|
| `successResponse($message = 'Success', $data = null, $customData = [], $code = null)` | `{"error": false, "message": trans($message), "data": ..., "code": 200}` merged with `$customData`, then `exit()`. |
| `errorResponse($message, $data = null, $code = null, $e = null)` | `{"error": true, "message": ..., "data": ..., "code": ..., "details": ...}`, then `exit()`. With an exception it logs the error and a redacted cURL reconstruction of the request. `details` holds the exception message, file and line only when `APP_DEBUG` is true; otherwise it is an empty string. |
| `validationError($message, $data = null)` | `errorResponse()` with `code` from `config('constants.RESPONSE_CODE.VALIDATION_ERROR')`. |
| `warningResponse($message, $data = null, $code = null)` | `{"error": false, "warning": true, ...}`, then `exit()`. |
| `noPermissionThenSendJson($permission)`, `noAnyPermissionThenSendJson(array)` | Send an error JSON if `Auth::user()` lacks the permission(s). |
| `noPermissionThenRedirect`, `noRoleThenRedirect`, `noAnyRoleThenRedirect`, `noAnyPermissionThenRedirect` | Send a redirect to `home` (or back) with `no_permission_message`. |
| `successRedirectResponse`, `errorRedirectResponse` | Send a redirect with a flashed `success` message or error. |
| `logErrorResponse($e)`, `logCurlRequest()` | `report($e)` plus a log line with the request parameters. Keys matching `pass`, `token`, `secret`, `key`, `signature`, `authorization`, `cookie` or `otp` are logged as `[redacted]`. |

Things to keep in mind:

- The `*Response` methods send the response and call `exit()`. Anything that would normally run after the controller does not: "after" middleware (so `SecurityHeaders` does not add its headers to these responses), session saving and `terminate` callbacks. For the same reason feature tests call these endpoints through a separate `php -S` server (`tests/Concerns/RunsAppServer.php`); see [Testing-and-CI](Testing-and-CI).
- There is no `config/constants.php`, so the default `code` of `errorResponse()` and `validationError()` is `null` unless the caller passes one. API controllers usually pass numeric codes such as `103`.
- Admin controllers that don't use the service return the same shape by hand: `response()->json(['error' => false|true, 'message' => trans('...')])`, which is what `formAjaxRequest()` expects.

## Key Composer dependencies

| Package | Locked version | Why it is used |
|---|---|---|
| `laravel/framework` | v12.70.0 | The framework. |
| `laravel/sanctum` | v4.3.3 | Bearer tokens for the mobile apps; tokens expire after `SANCTUM_EXPIRATION` minutes. |
| `spatie/laravel-permission` | 6.25.0 | Roles (`Super Admin`, `Teacher`, `Parent`, `Student`, plus custom staff roles) and permissions. |
| `laravel/ui` | v4.6.3 | Authentication controller traits (`AuthenticatesUsers` and others). |
| `barryvdh/laravel-dompdf` | v3.1.2 | PDFs: ID cards, certificates, results, fee receipts, academic calendar. |
| `maatwebsite/excel` | 3.1.70 | Student, attendance and online exam question imports; student export. |
| `intervention/image` | 3.11.10 | Image recompression with the GD driver (`UploadService`, `ImageService`, `resizeImage()`). |
| `enshrined/svg-sanitize` | 1.1.1 | SVG cleaning in `RejectDangerousUploads`. |
| `google/apiclient` | v2.20.1 | OAuth access token for FCM (only the `FirebaseCloudMessaging` service is kept, via `extra.google/apiclient-services` and the `Google\Task\Composer::cleanup` pre-autoload script). |
| `razorpay/razorpay`, `stripe/stripe-php`, `unicodeveloper/laravel-paystack` | 2.9.3, v18.2.0, `dev-l12-compatibility` | Payment gateway SDKs. The Paystack package comes from the `laravel-shift/laravel-paystack` fork (a `vcs` repository in `composer.json`) and reads its keys from `config/paystack.php`, which `SettingsConfigService` fills from the `paystack_*` settings. Flutterwave has no SDK; `FlutterwavePayment` calls its API directly. |
| `guzzlehttp/guzzle` | 7.16.0 | HTTP client behind `Http::` (reCAPTCHA verification in `WebController`, Stripe payment intent status lookup in `Api/Parent/FeesController`). |
| `awobaz/compoships` | 2.5.5 | Multi-column relationships; used by the `ExamClass` model. |
| `doctrine/dbal` | 3.10.6 | Schema tooling; several old migrations alter columns with `->change()`. |
| `dacoto/laravel-wizard-installer` | `dev-main` | The installer, loaded from the local path `packages/laravel-wizard-installer` (see [Installation-Wizard](Installation-Wizard)). It pulls in `dacoto/laravel-env-set` 2.2.0 for editing `.env`. |
| `laravel/tinker` | v2.11.1 | `php artisan tinker`. |

Development packages (`require-dev`): `barryvdh/laravel-debugbar`, `fakerphp/faker`, `laravel/pail`, `laravel/pint`, `laravel/sail`, `mockery/mockery`, `nunomaduro/collision`, `phpunit/phpunit` 11.5. `config/app.php` registers `Barryvdh\Debugbar\ServiceProvider` explicitly, so the app does not boot after `composer install --no-dev`; see [Deployment](Deployment#composer-dependencies).

## See also

- [Architecture](Architecture)
- [Repository-Layout](Repository-Layout)
- [Configuration](Configuration)
- [Security-Model](Security-Model)
- [Admin-Modules](Admin-Modules)
- [API-Overview](API-Overview)
- [Scheduled-Tasks-and-Commands](Scheduled-Tasks-and-Commands)
