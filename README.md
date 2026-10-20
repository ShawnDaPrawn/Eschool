# Eschool

School management system. This repository holds the admin panel, the two mobile apps, and the documentation site. Each directory is its own project. Install and run commands from that directory, not from the repository root.

## Layout

| Directory | Project |
| --- | --- |
| `admin-panel/` | Laravel admin panel and API |
| `e-school/` | Flutter student app (Dart package `eschool`) |
| `e-school-teacher/` | Flutter teacher app (Dart package `eschool_teacher`) |
| `documentation/` | Documentation site |
| `.github/workflows/` | Continuous integration |

The product changelog lives in [`documentation/changelog`](documentation/changelog/index.md).

## Admin panel

Requires PHP 8.3, Composer, and MySQL or MariaDB.

```bash
cd admin-panel
composer install
cp .env.example .env
php artisan key:generate
```

Set the database credentials in `.env`, then run `php artisan migrate`. Full setup notes are in [`admin-panel/README.md`](admin-panel/README.md).

## Mobile apps

Requires Flutter 3.44.9. Do not rename the Dart packages (`eschool` and `eschool_teacher`); those names are used by the import paths.

```bash
cd e-school
flutter pub get
flutter run
```

```bash
cd e-school-teacher
flutter pub get
flutter run
```

## Documentation

Requires Node.js 20 or newer. The site is built with npm. Sidebar configuration is in `documentation/sidebars/`.

```bash
cd documentation
npm ci
npm start
```

The site is served at `http://localhost:3000`.

### Firebase Hosting

`firebase.json` at the repository root deploys the documentation site to Firebase Hosting. Run this from the repository root with the [Firebase CLI](https://firebase.google.com/docs/cli):

```bash
firebase login
firebase use --add
firebase deploy --only hosting
```

The predeploy step runs `npm ci` and builds the site for `/`, with links on `https://<project-id>.web.app`. Builds without the `DOCS_URL` and `DOCS_BASE_URL` variables still target GitHub Pages at `/Eschool/`.
