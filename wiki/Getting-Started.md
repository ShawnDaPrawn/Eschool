This page gets every part of the project running on a development machine. Start with the admin panel, because both apps need its API. Then run whichever app you need. The docs site is optional. For a production server, see [Deployment](Deployment).

## Prerequisites

| Tool | Version | Needed for |
|---|---|---|
| PHP | 8.3 or newer, with `mbstring`, `intl`, `gd`, `zip`, `pdo_mysql`, `bcmath`, `exif`, `fileinfo`, `curl` | Admin panel |
| Composer | 2.x | Admin panel |
| MySQL or MariaDB | MariaDB 10.11 is what CI tests against | Admin panel |
| Node.js | 22 (CI version) | Docs site, and the panel's optional Vite build |
| Flutter | 3.44.9 stable (CI version). Dart SDK `>=3.8.0 <4.0.0` | Mobile apps |
| Android Studio / Xcode | Current | Mobile app builds |
| A Firebase project | | Push notifications and Crashlytics. The apps will not build without it |

## 1. Admin panel (server and API)

There are two ways to install the panel. Use the web installer for a normal install. Use the command line for a repeatable developer setup.

### Option A: web installer

```bash
cd admin-panel
composer install
cp .env.example .env
php artisan serve          # http://127.0.0.1:8000
```

Open `http://127.0.0.1:8000/install` and follow the wizard. It checks the server and folder permissions, writes the database settings to `.env`, runs the migrations, seeds roles, permissions, settings and the super admin, generates `APP_KEY` and creates the `storage` symlink. See [Installation Wizard](Installation-Wizard) for each step.

### Option B: command line

```bash
cd admin-panel
composer install
cp .env.example .env
# edit DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD and APP_URL in .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=InstallationSeeder
php artisan db:seed --class=AddSuperAdminSeeder
php artisan db:seed --class=EmailSeeder
php artisan storage:link
# mark the install as done, or every web request redirects to /install
echo '{"url":"http://127.0.0.1:8000"}' > storage/installed
php artisan serve
```

The installer package adds `ToInstallMiddleware` to the `web` group. It redirects every web request to `/install` until the file `storage/installed` exists, and the wizard's last step is what writes that file. A command-line install has to create the file itself.

`DatabaseSeeder` is empty, so a bare `php artisan db:seed` does nothing. The three seeders above are the same ones the wizard runs, and `tests/TestCase.php` seeds the same set. `DummyDataSeeder` adds optional sample data.

### First login

Sign in at `/login` with the super admin account that `AddSuperAdminSeeder` creates:

| Email | Password |
|---|---|
| `superadmin@gmail.com` | `superadmin` |

Change this password at once. Any server reachable from outside must not keep the default. (The credentials in `admin-panel/README.md` are out of date. These are the real ones.)

Next, set up the school in **Settings**: name, logo, time zone, the current session year, and the email, FCM and payment settings. See [Configuration](Configuration) and the end-user [admin panel guide](https://shawndaprawn.github.io/Eschool/admin-panel/).

### What runs where

| URL | What it is |
|---|---|
| `/` | Public school website (see [Public Website](Public-Website)) |
| `/login`, `/home` | Admin panel login and dashboard |
| `/install` | Web installer (blocked once installation is complete) |
| `/api/...` | REST API for the apps (see [API Overview](API-Overview)) |
| `/storage/...` | Uploaded files, served through the `public/storage` symlink |
| `/webhook/{stripe,razorpay,paystack,flutterwave}` | Payment gateway callbacks (see [Payments](Payments)) |

## 2. Mobile apps

Both apps work the same way. The steps below use `e-school`. For the teacher app, repeat them in `e-school-teacher`.

1. **Point the app at your server.** In `lib/utils/constants.dart`, set `baseUrl` to the panel's address. Do not add a trailing slash. Do not use `localhost` from an Android emulator: use `http://10.0.2.2:8000` there, or your machine's LAN IP for a physical device. The app builds `databaseUrl = '$baseUrl/api/'` and `storageUrl = '$baseUrl/storage/'` from it.
   ```dart
   const String baseUrl = 'https://navigateed-admin-972068206844.africa-south1.run.app';
   ```
2. **Firebase config is already in the repo** for project `navigateed-d8c06`: `android/app/google-services.json` and `ios/Runner/GoogleService-Info.plist` in each app. The Android build applies the `com.google.gms.google-services` and Crashlytics Gradle plugins, and `initializeApp()` calls `Firebase.initializeApp()`. Package names are `tech.tokensoft.navigateed` and `tech.tokensoft.navigateed.teacher`. If you rename them, download new files for the new ids (see [Mobile App Configuration](Mobile-App-Configuration)). To send push notifications, the panel also needs the service-account JSON from the same Firebase project (see [Notifications](Notifications)).
3. **Fetch packages and run:**
   ```bash
   cd e-school
   flutter pub get --enforce-lockfile
   flutter run
   ```
4. **Sign in** with a student, parent or teacher account you created in the admin panel. Students and parents use `e-school`. Teachers use `e-school-teacher`.

Release builds, signing and store settings are covered in [Building and Releasing Apps](Building-and-Releasing-Apps).

## 3. Documentation site (optional)

```bash
cd documentation
npm ci
npm start          # dev server with live reload
npm run build      # production build; fails on broken links
```

See [Documentation Site](Documentation-Site).

## 4. Run the checks CI runs

Run these from the repository root. Each line uses a subshell, so the working directory does not change between them.

```bash
# Admin panel: needs a MySQL/MariaDB database; set DB_* in .env first
(cd admin-panel && php artisan route:list > /dev/null && php artisan test)

# Each app
(cd e-school && flutter analyze --no-fatal-infos && flutter test)
(cd e-school-teacher && flutter analyze --no-fatal-infos && flutter test)

# Dependency audits
(cd admin-panel && composer audit --locked)
(cd documentation && npm audit --audit-level=critical)
```

[Testing and CI](Testing-and-CI) has the details, including how to create the test database.

## See also

- [Architecture](Architecture)
- [Configuration](Configuration)
- [Deployment](Deployment)
- [Troubleshooting and FAQ](Troubleshooting-and-FAQ)
