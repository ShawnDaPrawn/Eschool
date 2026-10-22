# Admin panel tests

Run them with `php artisan test`. They need MySQL or MariaDB, because the
migrations use MySQL-specific SQL. `TestCase` refuses to run unless the
database name ends in `_test` (see `phpunit.xml`), since tests drop every table.

## Calling the mobile API in tests

The API controllers answer through `App\Services\ResponseService`, which returns
the response (`return ResponseService::successResponse(...)`). Call the API
in-process, inside a `RefreshDatabase` transaction, with a Sanctum token:

```php
use RefreshDatabase;

$token = $user->createToken('test')->plainTextToken;

// The guard keeps the user of the previous request: forget it before a request
// made as someone else.
$this->app['auth']->forgetGuards();
$this->withToken($token)->getJson('/api/teacher/get-assignment')->assertOk();
```

`Sanctum::actingAs($user)` also works when the token itself isn't under test.
`TeacherApiSecurityTest` is the example to copy.

### Tests that still run a server

`ResponseService` used to call `exit()` after sending the response, which ended
the test run, so the older API suites serve the app from a separate `php -S`
process with `Tests\Concerns\RunsAppServer`. Keep using it for those suites
until they are moved, and for checks that need a real server (production error
pages, the panel's login form and cookies):

```php
use RunsAppServer;

protected function setUp(): void
{
    parent::setUp();
    if (! self::appServerRunning()) {      // once per test class
        $this->freshInstall();             // migrate and seed like the installer
        self::$school = DemoSchool::build();
        $this->startAppServer();
    }
}

// $this->apiClient($token)->get('/api/student/timetable')
```

The server can't see data inside a test transaction, so these tests migrate and
seed for real. Set `TEST_SERVER_LOG=/path/to/file` to keep the server's
`laravel.log` after the run.

`Tests\Support\DemoSchool` builds a small but complete school: classes,
teachers, students and parents, a timetable, lessons, assignments, exams,
online exams, fees, leaves, the calendar and the gallery. Its dates are relative
to today, so states like "upcoming" or "ongoing" are the same on every run.

## API contract snapshots

`ApiContractTest` calls every endpoint the apps use and compares each
response's shape with `tests/snapshots/api/<app>/<call>.json`. The shape is the
keys and value types, plus the `error`, `message` and `code` values. Apps
already on people's phones depend on these, so the test fails when one changes.

- **After an intended API change**, regenerate the snapshots and review the
  diff in the PR:
  `UPDATE_API_SNAPSHOTS=1 php artisan test --filter=ApiContractTest`
- **When refactoring**, also compare the actual data. Save every full response
  before and after the change, then diff the two folders:
  `API_RESPONSES_DIR=/tmp/before php artisan test --filter=ApiContractTest`

## Static analysis

`vendor/bin/phpstan analyse --memory-limit=2G` runs Larastan; CI runs it too.
`phpstan-baseline.neon` lists the errors that predate it, so only new errors
fail. When you fix one, regenerate the baseline with `--generate-baseline`, or
delete its entry.

Larastan can't see a call to a missing method on a controller, because Laravel's
base `Controller` has a `__call` that accepts any name. `ControllerMethodCallsTest`
checks that every `$this->method()` in a controller exists, which is what goes
wrong when methods move between controllers. It also parses `app/` and fails on
any `ResponseService::successResponse()` (or another method that builds a
response) whose value is not returned, or thrown as
`new HttpResponseException(...)` from a helper that has to stop the request.
