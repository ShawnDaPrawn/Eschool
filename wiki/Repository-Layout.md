This repository is a monorepo. It holds the server, both mobile apps, the end-user documentation site and the CI configuration. Each top-level project builds and runs on its own. They are only linked at runtime, through the REST API and the `/storage` URL.

## Top level

```text
Eschool/
├── README.md                            What this repository is, and where to start
├── CONTRIBUTING.md                      How to change a project and what to run
├── admin-panel/                         Laravel 12 app: admin panel, public website, REST API
├── e-school/                            Flutter app for students and parents
├── e-school-teacher/                    Flutter app for teachers
├── packages/eschool_core/               Dart package both Flutter apps depend on (code shared by the two)
├── documentation/                       Docusaurus site with end-user setup and feature guides
├── wiki/                                Source of this wiki (published to the Wiki tab)
└── .github/workflows/                   CI, and the job that publishes this wiki
```

| Project | Language / framework | Entry point | Talks to |
|---|---|---|---|
| `admin-panel` | PHP 8.3, Laravel 12, MySQL/MariaDB | `public/index.php` | Database, Firebase Cloud Messaging, payment gateways, SMTP |
| `e-school` | Dart 3.8+, Flutter | `lib/main.dart` | `admin-panel` at `/api/student/*`, `/api/parent/*` and shared `/api/*` routes |
| `e-school-teacher` | Dart 3.8+, Flutter | `lib/main.dart` | `admin-panel` at `/api/teacher/*` and shared `/api/*` routes |
| `packages/eschool_core` | Dart 3.8+, Flutter package | `lib/eschool_core.dart` | Nothing. Both apps depend on it by path |
| `documentation` | Node 22, Docusaurus 3 | `docusaurus.config.js` | Nothing at runtime. It is a static site on GitHub Pages |

## `admin-panel/`

```text
admin-panel/
├── app/
│   ├── Actions/Transfers/      Copy class subjects, teachers, timetables, fee types, grades and leave settings into a new session year
│   ├── Console/Commands/       Artisan commands (chat cleanup, fee cleanup)
│   ├── Exports/  Imports/      Maatwebsite Excel: student export; student, attendance and online-exam question imports
│   ├── Helpers/                Global helper functions (settings, notifications)
│   ├── Http/
│   │   ├── Controllers/        Admin panel and public website controllers
│   │   ├── Controllers/Api/    REST API: ApiController, then Student/, Parent/ and Teacher/ (one controller per area)
│   │   ├── Controllers/Auth/   Web login and password reset
│   │   ├── Middleware/         Role checks, session-year guards, upload filter, security headers
│   │   ├── Requests/  Resources/
│   ├── Models/                 Eloquent models (76)
│   ├── Rules/                  Custom validation rules (AfterNow, PlainName, YouTubeUrl, unique lesson/topic)
│   └── Services/               Business logic (fees, exams, reports, uploads, mail, ID cards)
│       └── Payment/            Gateway adapters: Stripe, Razorpay, Paystack, Flutterwave
├── bootstrap/app.php           Middleware aliases, route files, exception handling
├── config/                     Laravel config plus global.php, installer.php, paystack.php
├── database/
│   ├── migrations/             92 migrations
│   └── seeders/                Installation, super admin, dummy data, email templates
├── packages/laravel-wizard-installer/   Local fork of the web installer (served at /install)
├── public/                     Web root: index.php, compiled/static assets under public/assets
├── resources/
│   ├── lang/                   Panel translations (en.json, ar.json, Laravel's en/*.php)
│   └── views/                  Blade views, one folder per module, plus web/ for the public site
├── routes/
│   ├── web.php                 Admin panel, public site, webhooks
│   ├── api.php                 Mobile app API
│   └── console.php             Scheduled tasks
├── storage/                    Uploaded files (storage/app/public), logs, cache
├── tests/Feature/              PHPUnit feature tests (security, routes, installer, payments)
├── .htaccess                   Lets the project root be a document root on shared hosting: forwards everything to public/ and blocks dotfiles and manifests
└── composer.json, package.json, vite.config.js, phpunit.xml
```

See [Admin Panel Overview](Admin-Panel-Overview) for how these pieces fit together.

## `e-school/` and `e-school-teacher/`

Both apps have the same layout:

```text
e-school/
├── lib/
│   ├── main.dart               Calls initializeApp() in app/app.dart (Firebase, Crashlytics, notifications, Hive)
│   ├── app/                    app.dart (MaterialApp, providers), routes.dart, appLocalization.dart
│   ├── cubits/                 flutter_bloc Cubits, one per feature or screen
│   ├── data/models/            Plain Dart models parsed from API JSON
│   ├── data/repositories/      API calls grouped by domain
│   ├── ui/screens/             Screens, grouped by feature
│   ├── ui/widgets/             Shared widgets
│   └── utils/                  api.dart (endpoint list and Dio client), constants.dart (names mapped from AppConfig's build settings), labels, Hive keys, notifications
├── assets/
│   ├── languages/*.json        UI translations
│   ├── images/, animations/    Images, branding and Lottie files
├── google_fonts/               Bundled font files (no runtime font download)
├── android/  ios/              Platform projects
├── test/                       translations_test.dart (language files complete), theme_colors_test.dart (no hard-coded colours)
├── pubspec.yaml / pubspec.lock
└── analysis_options.yaml
```

The Dart package names are `eschool` and `eschool_teacher`. Every import uses them, so do not rename them. The comment at the top of each `pubspec.yaml` says the same. To change the name users see, follow [Mobile App Configuration](Mobile-App-Configuration).

## `packages/eschool_core/`

Code that both Flutter apps need, kept once instead of copied into each. Both apps list it as a path dependency (`eschool_core: path: ../packages/eschool_core`) and import it with `import 'package:eschool_core/eschool_core.dart';`.

```text
packages/eschool_core/
├── lib/eschool_core.dart       The one public library: exports everything below
├── lib/src/api/                ApiClient (the Dio calls and their error handling), ApiException, ApiErrorCodes
├── lib/src/models/             Models identical in both apps (gallery, chat settings, event schedule, ...)
├── lib/src/theme/              The Material 3 theme both apps use: brand colours, AppTheme, StatusColors
├── lib/src/utils/              Helpers (file name sanitising)
├── lib/src/widgets/            Widgets that take everything they need as parameters
├── test/                       Unit tests and the theme's screenshot test (test/goldens/), run by CI like the apps' tests
└── pubspec.yaml / pubspec.lock
```

What belongs here and how to move a file in is in the package's [README](https://github.com/ShawnDaPrawn/Eschool/blob/main/packages/eschool_core/README.md). Files in the package use `snake_case` names; the apps' older files use `camelCase`.

The duplication that remains is mostly code that depends on app-specific helpers (`UiUtils`, label keys, `Api` endpoints and `routes.dart`), which differ between the apps today. Those have to be shared first before the screens, cubits and repositories built on them can move.

## `documentation/`

The Docusaurus project behind https://shawndaprawn.github.io/Eschool/. Each section is its own docs plugin, with the pages in `admin-panel/`, `mobile-app/`, `web-page/`, `features/`, `faqs/`, `support/` and `changelog/`. Sidebar config for the four autogenerated sections is in `sidebars/`. Images are in `static/images/`. The product changelog is `changelog/index.md`. See [Documentation Site](Documentation-Site).

## `wiki/`

The Markdown source of this wiki. Each file is one page, and the file name is the page title. `_Sidebar.md` and `_Footer.md` are shown on every page. The [Publish wiki workflow](https://github.com/ShawnDaPrawn/Eschool/blob/main/.github/workflows/wiki.yml) copies the folder to the Wiki tab on every push to `main`. See [Contributing](Contributing#editing-this-wiki).

## See also

- [Architecture](Architecture)
- [Getting Started](Getting-Started)
- [Admin Panel Overview](Admin-Panel-Overview)
- [Mobile Apps Overview](Mobile-Apps-Overview)
