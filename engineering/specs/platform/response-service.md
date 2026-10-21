# ResponseService returns instead of exiting (Phase 0, feature 0.3)

Build Bible section 6, build step 3. Work stream S1. No user-visible change: every API and panel answer has the same status and body as before. What changes is underneath: a response now travels back through Laravel's middleware, code after it never runs because it is returned rather than because PHP stopped, and the API can be tested in-process.

## Before

`App\Services\ResponseService::successResponse()`, `errorResponse()`, `warningResponse()` and `validationError()` called `response()->json(...)->send()` and then `exit()`. About 560 call sites in 52 controllers and two access traits relied on that to stop the request. Consequences:

- The response skipped the middleware's after-handling: no `SecurityHeaders` (HSTS, `nosniff`, frame and referrer headers), no rate-limit headers, no CORS headers, no session save or queued cookies, no terminating middleware.
- Nothing after a response could run (logging, events, the outbox the kernel adds in Phase 0).
- A test calling an endpoint in-process ended the test run, so every API test needed `Tests\Concerns\RunsAppServer` (a separate `php -S` server, a real migrate and seed, no transactions).
- `successRedirectResponse()` and `errorRedirectResponse()` sent their redirect and then returned it (unused, but a returned redirect would have been sent twice).

## After

| Method | Now |
| --- | --- |
| `successResponse`, `warningResponse` | return the `JsonResponse` |
| `errorResponse`, `validationError` | return the response. When given an `HttpResponseException`, `errorResponse` returns that exception's response unchanged and logs nothing (see below) |
| `successRedirectResponse`, `errorRedirectResponse` | return the redirect without sending it |
| `noPermissionThenSendJson`, `noAnyPermissionThenSendJson` | throw `HttpResponseException` with the error response (they are guards: callers call them as a statement and expect the request to stop) |
| `noPermissionThenRedirect`, `noRoleThenRedirect`, `noAnyRoleThenRedirect`, `noAnyPermissionThenRedirect` | unchanged (they already threw) |
| `logErrorResponse`, `logCurlRequest`, `loggable` | unchanged |

No other `exit` or `die` ends a request anywhere in `app/`.

### Call sites

A codemod (a `nikic/php-parser` script that inserts text at AST positions, so formatting is untouched) rewrote every statement-level call:

- in a controller action: `ResponseService::x(...);` became `return ResponseService::x(...);` (520 sites; 40 were already returned);
- in a helper whose callers relied on `exit()` to stop, `ParentAccess::resolveChild()` and `TeacherAccess::currentTeacher()`: `throw new HttpResponseException(ResponseService::x(...));` (2 sites). Every helper in `TeacherAccess` that calls `currentTeacher()` passes the exception on.

Reviewed by hand:

- Five teacher API actions declared `: void` now declare `: JsonResponse`.
- `StaffController::destroy()` had `-ResponseService::successResponse(...)` (a stray minus that never ran because of the exit); it is now returned.
- Calls inside loops (16) return from the action as `exit()` stopped it; none sits inside an open database transaction, a `finally` block or a closure.
- The helpers are called inside `try` blocks whose `catch (Throwable|Exception $e)` hands `$e` to `errorResponse()`. That is why `errorResponse()` passes a thrown response through: the catch block returns the helper's answer (for example code 105 "Invalid Child ID Passed." or the teacher 403) exactly as when the helper exited, and the `DB::rollBack()` some of those catch blocks run matches the implicit rollback `exit()` caused. Every such catch block was checked to pass `$e`.

### Guard against regressions

`tests/Unit/ControllerMethodCallsTest::test_every_response_from_response_service_is_returned_or_thrown` parses every file in `app/` and fails on any call to a response-building `ResponseService` method whose value is not returned or thrown (directly, through a ternary, or wrapped in `throw new HttpResponseException(...)`).

## User stories

- **Every profile:** nothing changes in what the apps and the panel receive.
- **Engineer:** I write `return ResponseService::successResponse(...)`; code after a response does not run; I can test an endpoint with `$this->getJson()` in a transaction.
- **Deployer and security:** API responses now carry the security headers, rate-limit headers and CORS headers the middleware adds to every other response.

## Acceptance tests

| Given | When | Then | Test |
| --- | --- | --- | --- |
| The 162 API calls the apps make | `ApiContractTest` runs | every snapshot in `tests/snapshots/api/` is unchanged | `ApiContractTest` |
| The same calls before and after the change, full bodies saved with `API_RESPONSES_DIR` | the two folders are diffed | identical apart from timestamps, the test server's port, fresh tokens and PDF creation dates | self-test step 2 (done on the S1 branch: 162 of 162 identical) |
| A call that builds a response | the code is parsed | the response is returned or thrown | `ControllerMethodCallsTest` |
| `successResponse`, `errorResponse`, `warningResponse`, `validationError` | they are called | they return the envelope and the caller carries on | `ResponseServiceTest` |
| A helper threw its response and a catch block hands the exception to `errorResponse()` | the catch block returns | the helper's response, not logged | `ResponseServiceTest::test_a_thrown_response_passes_through_error_response`, `TeacherApiSecurityTest` (teacher without a record gets 403 from inside `try` blocks) |
| A user without the permission | a permission guard runs | it throws the JSON error | `ResponseServiceTest::test_permission_guards_throw_the_error_response` |
| The teacher API | its security tests run in-process with Sanctum tokens | they pass without `RunsAppServer` | `TeacherApiSecurityTest` |
| The whole panel suite and Larastan | CI runs | green | CI |

## Data changes, endpoints, screens, notifications, module switch

None. Every route keeps its status codes and bodies. Response headers gain what the middleware adds to any other response (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Strict-Transport-Security` over HTTPS, `X-RateLimit-*`, CORS, the session cookie on panel AJAX routes); no client relies on their absence.

## Security notes

- API responses now get the headers `SecurityHeaders` sets; before, every `ResponseService` answer went out without them.
- Rate-limit headers and 429s are unchanged in behaviour; only the headers on successful answers are new.
- A panel AJAX action that answered through `ResponseService` now saves the session at the end of the request like every other action.
- No change to who may call what: role middleware, `CheckChild` and the access traits run as before.

## Testing in-process

With `exit()` gone an API test can use Laravel's test client and a `RefreshDatabase` transaction:

```php
use RefreshDatabase;

$token = $user->createToken('test')->plainTextToken;
$this->app['auth']->forgetGuards();   // between requests made as different users
$this->withToken($token)->getJson('/api/teacher/get-assignment')->assertOk();
```

`Sanctum::actingAs($user)` works too when the token itself is not under test. `TeacherApiSecurityTest` is the first class moved; `RunsAppServer` stays for the other API and panel suites until they are moved (Bible: keep it until every suite is moved).

## Self-test steps

1. `php artisan test --filter=ApiContractTest`: passes with no snapshot change.
2. Save full responses before and after: `API_RESPONSES_DIR=/tmp/before php artisan test --filter=ApiContractTest` on the commit before the change, `API_RESPONSES_DIR=/tmp/after ...` after, then diff the folders ignoring timestamps, ports, tokens and PDF dates.
3. In the panel, open a dashboard widget as a teacher without its permission: the JSON error appears as before.
4. In the parent app, open a child screen with another family's `child_id` (or curl it): `{"error": true, "code": 105}` as before.
5. `curl -I` any API endpoint: `X-Content-Type-Options: nosniff` and `X-RateLimit-Limit` are now present.
