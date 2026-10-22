This page shows how the parts of eSchool fit together: what runs where, how requests move through the server, and how the apps, Firebase and the payment gateway connect to it. It then describes where the system is going as NavigateEd (the target architecture of the Build Bible, section 3) and how the current code grows into it. Each topic has its own detailed page, linked from the sections below.

## Deployment model

Each install serves **one school**. There is no multi-school tenancy yet: all data belongs to the single school configured in Settings (Phase 1 adds schools and `school_id`; see [Where it is going](#where-it-is-going)). One Laravel application in `admin-panel/` does three jobs on the same domain and database:

1. **Admin panel.** Server-rendered Blade pages for staff, behind session login at `/login`.
2. **Public website.** The school's marketing site and online admission form at `/`.
3. **REST API.** JSON endpoints under `/api` for the two Flutter apps, with Laravel Sanctum token authentication.

```mermaid
flowchart TB
    subgraph Browser
        AP["Admin panel pages"]
        PW["Public website"]
    end
    subgraph Mobile
        SA["e-school<br/>(student / parent)"]
        TA["e-school-teacher"]
    end
    subgraph Laravel["admin-panel"]
        direction TB
        MW["Global middleware<br/>SecurityHeaders"]
        WEBG["web group<br/>session, CSRF, language,<br/>DemoMiddleware, RejectDangerousUploads,<br/>ToInstallMiddleware"]
        APIG["api group<br/>throttle:api, RejectDangerousUploads"]
        WC["Controllers/*<br/>+ Services"]
        AC["Controllers/Api/*<br/>Student, Parent, Teacher, Api"]
        M["Eloquent models"]
    end
    DB[("MySQL / MariaDB")]
    ST[("storage/app/public<br/>served at /storage")]
    FCM["Firebase Cloud Messaging<br/>HTTP v1"]
    PGW["Stripe"]
    SMTP["Mail server or provider"]

    AP --> MW
    PW --> MW
    SA --> MW
    TA --> MW
    MW --> WEBG --> WC
    MW --> APIG --> AC
    WC --> M --> DB
    AC --> M
    WC --> ST
    AC --> ST
    WC --> FCM
    AC --> FCM
    WC --> SMTP
    AC --> SMTP
    PGW -- "POST /webhook/{gateway}" --> WEBG
    FCM --> SA
    FCM --> TA
```

## Server side

### Routing

Routes are registered in [`bootstrap/app.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/bootstrap/app.php):

| File | Prefix | Used by |
|---|---|---|
| [`routes/web.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/routes/web.php) | `/` | Public website, login and password reset, every admin module, `/webhook/*` payment callbacks |
| [`routes/api.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/routes/api.php) | `/api` | `/api/student/*`, `/api/parent/*`, `/api/teacher/*`, plus shared endpoints such as `/api/forgot-password` and `/api/logout` |
| [`routes/console.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/routes/console.php) | — | Scheduled tasks (see [Scheduled Tasks and Commands](Scheduled-Tasks-and-Commands)) |
| `packages/laravel-wizard-installer/routes/routes.php` | `/install` | The web installer (see [Installation Wizard](Installation-Wizard)) |
| [`routes/health.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/routes/health.php) | `/up/worker` | Worker health for the uptime checks (loaded by `ObservabilityServiceProvider`, outside the web and api groups) |
| (framework) | `/up` | Laravel health check |

### Middleware

`bootstrap/app.php` sets up the middleware:

- **Every response:** `AssignRequestId` (outermost: gives the request an id, puts it in the log context, returns `X-Request-Id`) and `SecurityHeaders`.
- **`web` group:** `LanguageManager` (UI language from the session), cookies and session, `ShareErrorsFromSession`, `SubstituteBindings`, `DemoMiddleware` (blocks writes when `DEMO_MODE=true`) and `RejectDangerousUploads`. The installer service provider prepends `ToInstallMiddleware`, which sends every request to `/install` until `storage/installed` exists. CSRF protection applies to everything except `webhook/*`.
- **`api` group:** Sanctum's stateful-frontend middleware, `throttle:api` (60 requests a minute per user, or per IP when not signed in), `SubstituteBindings` and `RejectDangerousUploads`.
- **Route aliases:**

| Alias | Purpose |
|---|---|
| `Role` | `CheckRole`, used on the admin panel route group |
| `role:Teacher` | spatie role check on the teacher API |
| `permission:...` | spatie permission checks |
| `checkChild` | `CheckChild`: the parent's `child_id` must belong to them |
| `checkStudent` | `CheckStudent`: the `student_id` a teacher sends must be in a class section where they are class teacher |
| `student.session.active` | `EnsureStudentSessionIsActive` |
| `parent.children.session.active` | `EnsureParentChildrenSessionIsActive` |
| `throttle:login` | 5 attempts a minute per account and path on login and password reset endpoints |

The full list is on [Admin Panel Overview](Admin-Panel-Overview#middleware-reference). The security reasons behind each layer are on [Security Model](Security-Model).

### Code organisation

Controllers stay thin where they can and hand work to `app/Services`: fees collection and reports, exam reports, online-exam scoring, uploads, images, ID cards, mail and session years. Gateway code sits behind one interface in `app/Services/Payment`. Global helper functions are in `app/Helpers`. `settings_helper.php` reads the database-backed settings (`getSettings()`, `schoolSetting()`, `schoolLogoUrl()`, date and time formats, grade lookup). `notification_helper.php` sends FCM pushes. List screens in the panel use Bootstrap Table, which calls a JSON endpoint per module. `BootstrapTableService` builds those responses. Details: [Admin Panel Overview](Admin-Panel-Overview).

### Data

- **Relational data:** MySQL/MariaDB, in 91 migrations. Most academic records carry a `session_year_id`, so each school year is its own slice of data. Starting a new session year copies class subjects, teacher assignments, timetables, fee types, exam grades and leave settings forward (`app/Actions/Transfers/*`, called from `SessionYearService`). See [Database Schema](Database-Schema) and [Academic Structure](Academic-Structure).
- **Settings:** most runtime configuration is rows in the `settings` table, edited in the admin UI. That covers school details and logos, the FCM project, payment keys, chat settings and the app version numbers. The table is the only place these are stored: the admin forms never write to `.env`. On every boot, `SettingsConfigService` (called from `AppServiceProvider::boot()`) copies the school name, time zone and mail server settings from the table into Laravel config, falling back to `.env` where a setting is empty. That keeps `php artisan optimize` and `config:cache` safe. `.env` holds the infrastructure settings: database, app URL, debug and demo flags, and default mail. See [Configuration](Configuration).
- **Files:** uploads go to the `public` disk (`storage/app/public`). They are served at `/storage/...` through the `public/storage` symlink. The apps build file URLs from `storageUrl = '$baseUrl/storage/'`.
- **Roles and permissions:** spatie/laravel-permission tables, seeded by `InstallationSeeder`. See [Roles and Permissions](Roles-and-Permissions).

### Background work

One Docker image runs in three roles, chosen by `CONTAINER_ROLE` ([Deployment](Deployment#container-roles)):

```mermaid
flowchart LR
    CS["Cloud Scheduler<br/>every minute"] --> J["navigateed-scheduler<br/>(Cloud Run Job)<br/>schedule:run"]
    W["navigateed-admin<br/>web role"] -- "queue jobs" --> DB[("Cloud SQL<br/>jobs table")]
    J -- "queue jobs" --> DB
    DB --> Q["navigateed-worker<br/>queue:work"]
    Q --> OUT["Mail, FCM push"]
    W -. "migrations, seeders<br/>(web role only)" .-> DB
```

- **Queue.** Production uses the database queue (`QUEUE_CONNECTION=database`), emptied by `navigateed-worker` (`queue:work --tries=3 --timeout=120`). Phase 0 moves mail and pushes onto it; until those jobs land, mail and pushes are still sent during the request. Locally `sync` runs jobs inside the request. A job that fails three times is logged as "Queued job failed", and `GET /up/worker` fails when jobs stop being picked up.
- **Schedule.** `routes/console.php` holds the whole schedule. On Cloud Run, Cloud Scheduler starts the `navigateed-scheduler` job every minute, which runs one `schedule:run`; on your own server, the usual cron entry. See [Scheduled Tasks and Commands](Scheduled-Tasks-and-Commands).
- **Deploy-time work** (migrations, the installer seeders) runs only in the web role when a new revision starts.

## Client side

### Admin panel UI

Blade templates in `resources/views`, styled with Bootstrap. jQuery and plugins under `public/assets` drive the pages: Bootstrap Table, Select2, TinyMCE/Jodit editors, KaTeX for exam questions, ApexCharts on the dashboard. The panel loads these pre-built assets directly. The Vite setup in `package.json` is the Laravel default and is not needed to run the panel. Translations reach the browser through `/common/js/lang`.

### Mobile apps

Both Flutter apps use the same layering:

```mermaid
flowchart LR
    UI["Screen / widget"] --> C["Cubit<br/>(flutter_bloc)"]
    C --> R["Repository<br/>lib/data/repositories"]
    R --> API["Api helper<br/>lib/utils/api.dart (Dio)"]
    API -- "HTTPS + Bearer token" --> S["/api/... on admin-panel"]
    R --> H[("Hive boxes<br/>auth, settings")]
```

- The server address, environment and flavour are build settings (`--dart-define` `API_BASE_URL`, `ENV`, `FLAVOR`) read by `AppConfig` in `eschool_core`; the same code builds staging and production.
- On login the API returns a Sanctum token. The app keeps it in the Hive `authBoxKey` box and sends it as `Authorization: Bearer <token>` on every call. Tokens expire after `SANCTUM_EXPIRATION` minutes, 43200 (30 days) by default.
- Push notifications come in through `firebase_messaging` and are shown with `awesome_notifications`. Crashes go to Firebase Crashlytics.
- The student/parent app takes payments through Stripe (`flutter_stripe`) or a webview (`webview_flutter`). The server never trusts the app's word that a payment succeeded. It confirms through the gateway API or the gateway's signed webhook.

See [Mobile Apps Overview](Mobile-Apps-Overview).

## Key flows

### Logging in from an app

```mermaid
sequenceDiagram
    participant App
    participant API as /api/{role}/login
    participant DB
    App->>API: credentials + fcm_id + device_type
    Note over API: throttle:login (5/min per account)
    API->>DB: verify user and role, store fcm_id
    API-->>App: { error: false, message, data: profile, code, token }
    App->>App: save token in Hive
    App->>API: later calls with Authorization: Bearer token
```

Each role has its own login endpoint. Students sign in with their GR (admission) number, which is stored in `users.email`. Parents and teachers sign in with email. Before it issues a token, the endpoint checks that the account has the right role and is active. For students it also checks that they have an active `student_sessions` row for the current session year. Every API response, success or error, uses the envelope `error`, `message`, `data`, `code` from `ResponseService`. See [API Overview](API-Overview).

### Push notifications

An admin action (an announcement, a custom notification, a fee reminder) or an app action (a chat message, a new assignment, a leave decision) calls `sendSimpleNotification()` or `sendNotificationToTopic()` in `app/Helpers/notification_helper.php`. The helper gets an OAuth access token from the Firebase service-account file, looks up each recipient's `users.fcm_id` and `device_type`, and posts to `https://fcm.googleapis.com/v1/projects/{project_id}/messages:send`. The `project_id` comes from the settings table. See [Notifications](Notifications).

### Online fee payment

The app asks the API to start a payment. The server works out the amount from the fee records, never from the app, and creates the gateway order or intent plus a `payment_transactions` row. The app completes checkout with the gateway. The gateway then calls `/webhook/{gateway}`, the server verifies the signature or secret hash, and only then are the fees marked paid. See [Payments](Payments) for the per-gateway details and a sequence diagram.

## Cross-cutting concerns

| Concern | Where |
|---|---|
| Authentication | Web session guard (`/login`), Sanctum tokens (`/api/*/login`) |
| Authorization | spatie permissions on admin routes and controllers, role middleware and per-query scoping in the API. See [Roles and Permissions](Roles-and-Permissions) |
| Localization | `LanguageManager` and `languages` table on the server, JSON language files in each app. See [Localization](Localization) |
| Security headers, upload filtering | `SecurityHeaders`, `RejectDangerousUploads`, `UploadService`. See [Security Model](Security-Model) |
| Releases | Container images deployed by GitHub Actions: staging on every merge to `main`, production from a `v*` tag after approval (the in-panel ZIP updater was removed in Phase 0). See [Deployment](Deployment#deploys) |
| Observability | JSON logs with a request id, user id, school id and route on every line (`LOG_CHANNEL=json`), Cloud Error Reporting, uptime checks on the panel, API and worker; Firebase Crashlytics and Analytics in the apps. See [Configuration](Configuration#logging) |

## Where it is going

NavigateEd grows this code into a multi-school platform without re-platforming (Build Bible section 3; decision record [ADR 0001](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/adr/0001-seed-kernel-and-domains.md)). The final shape is the same Laravel app with a **platform kernel** added underneath and **domains** added beside the existing code, serving four clients:

```mermaid
flowchart TB
    subgraph Clients
        LP["Learner and parent<br/>e-school app and web portal"]
        TA["Teacher app<br/>facilitator and vendor flavours, web portal"]
        AP["Admin panel<br/>redesigned, plus SaaS admin"]
        PP["Public pages<br/>intake forms, school page"]
    end
    subgraph App["admin-panel: one Laravel app on Cloud Run"]
        API["API v1 kept unchanged · API v2 with an OpenAPI contract · panel and public pages"]
        subgraph Domains
            D1["People"] --- D2["Academics"] --- D3["Communication"] --- D4["Money"]
            D5["Activities"] --- D6["Wellbeing"] --- D7["Growth"] --- D8["Platform"]
        end
        subgraph Kernel["Platform kernel (app/Services/Platform)"]
            K["Tenancy · Identity · Modules · Notifier · Outbox · Audit · Ledger · AiGateway · Files"]
        end
        API --> Domains --> Kernel
    end
    Clients --> API
    Kernel --> S1[("Cloud SQL<br/>MySQL 8, one database")]
    Kernel --> S2[("Cloud Storage")]
    Kernel --> S3["Queue worker"]
    Kernel --> S4["Scheduler"]
    Kernel --> S5["Reverb<br/>live updates"]
    Kernel --> S6["Converter<br/>Python, MarkItDown"]
    Kernel --> S7["FCM and e-mail"]
    Kernel --> S8["Payment providers"]
    Kernel --> S9["AI and speech<br/>behind AiGateway"]
```

| Layer | What it is |
|---|---|
| Clients | Two Flutter shells over shared packages (`packages/eschool_core`, `eschool_api`, `eschool_session`, `eschool_notifications`, `eschool_features/*`), each also built for the web; the redesigned panel; public pages |
| API | v1 stays exactly as the installed apps use it (fields added, never renamed or removed); v2 under `/api/v2` carries everything new, with `admin-panel/openapi.yaml` as the contract and `GET /api/v2/me/context` telling the apps who the user is and what each school has switched on |
| Domains | People, Academics, Communication, Money, Activities, Wellbeing, Growth, Platform. Each uses Laravel's folders with a subfolder per domain: `app/Models/<Domain>/`, `app/Services/<Domain>/` (the only place with business rules), `app/Policies/<Domain>/`, `app/Http/Requests/<Domain>/`, thin controllers, Resources, and tests per domain |
| Kernel | Tenancy (`BelongsToSchool`, the current school from the membership), Identity (memberships, guardian links, approvals), Modules (per-school switches), Notifier, Outbox (domain events in the same transaction), Audit, Ledger (integer cents, idempotent), AiGateway, Files, and per-school Settings |
| Services | `navigateed-admin` (web), `navigateed-worker` (queue), `navigateed-scheduler` (every minute), later `navigateed-converter` and `navigateed-reverb`; Cloud SQL with `school_id` everywhere; a bucket per environment; Secret Manager; all in `africa-south1` |

### How the seed grows

1. **Phase 0** adds the kernel's scaffolding without changing behaviour: the queue and scheduler, outbox, audit, money in cents, the API v2 skeleton with `/me/context`, the app packages and web builds, the panel's new navigation, staging, and the CI gates.
2. **Phase 1** adds `schools` and `school_id` to every table with a back-fill to one school, turns on Spatie's teams mode, and adds memberships, guardian links and approvals, module switches and notification rules. The installed apps keep working because v1 keeps answering.
3. **Each later phase** adds domains beside the existing code, moves the rules of any legacy controller it touches into a domain service that v1 and v2 both call, and replaces a screen only when the new one is at least as good.
4. **Retirement** comes last: a v1 endpoint is removed only when the request logs (the `route` field on every JSON log line) show no callers for a full term.

Until then, everything above this section describes the code as it runs today. The phase plans are in [`engineering/phases/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/engineering/phases) and the decisions in [`engineering/adr/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/engineering/adr).

## See also

- [Repository Layout](Repository-Layout)
- [Admin Panel Overview](Admin-Panel-Overview)
- [API Overview](API-Overview)
- [Mobile Apps Overview](Mobile-Apps-Overview)
- [Security Model](Security-Model)
