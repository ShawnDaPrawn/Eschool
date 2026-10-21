# Removals and seed defects (Phase 0, feature 0.2)

Build Bible section 6, build step 2, and the small defects listed in section 2. Work stream S1. Nothing the users see changes, except that the System Update menu item and the Razorpay, Paystack and Flutterwave tabs disappear from the admin panel and the apps stop offering those gateways.

## What is removed

| Item | Why | Replaced by |
| --- | --- | --- |
| In-panel System Update (`SystemUpdateController`, routes `GET`/`POST system-update`, view, script, stylesheet, sidebar item, `config/system_update.php`, `SYSTEM_UPDATE_ENABLED`) | It installed an uploaded zip over the app's code: a stolen Super Admin session was enough to run any code on the server | Releasing a new image; migrations and seeders at deploy time |
| `GET storage-link`, `GET migrate`, `GET seeder_install` | Deploy-time commands run from a link | `docker/entrypoint.sh`: `ln -sfn` for the storage link, `migrate --force`, `InstallationSeeder` and `EmailSeeder` on every start |
| Commented-out routes at the end of `routes/web.php` (`rollback`, `test`, `storage-link`, `command`) | Dead code | Nothing |
| Razorpay, Paystack and Flutterwave (`RazorpayPayment`, `PaystackPayment`, `FlutterwavePayment`, `config/paystack.php`, their `config/services.php` entries, the Paystack keys in `SettingsConfigService`, their fields and validation on the Fees configuration page, `POST webhook/{razorpay,paystack,flutterwave}`, `GET response/{paystack,flutterwave}/success`, the Composer packages `razorpay/razorpay` and `unicodeveloper/laravel-paystack` and the `laravel-shift/laravel-paystack` VCS repository) | Gateways NavigateEd will not use (Bible, Appendix F) | Stripe now; a South African gateway in Phase 5 |
| `EnsureUserIsTeacher` middleware | Never registered or used; the teacher groups use `role:Teacher` | Nothing |
| Route registration in `RouteServiceProvider::boot()` | Loaded every route a second time on top of `withRouting()` in `bootstrap/app.php` | `bootstrap/app.php` only |

## Seed defects fixed

| Defect | Fix |
| --- | --- |
| `docker/provision-admin.php` seeds `Database\Seeders\emailseeder`; autoloading is case-sensitive on Linux, so a fresh container install stopped | Names `EmailSeeder`; `DeploySeedersTest` checks every seeder the container runs exists under that exact name |
| Model `EmailTempalate` | Renamed `EmailTemplate`, `$table = 'email_tempalates'` (no migration) |
| `hasRole('Admin')` in `Students::scopeOfTeacher()` and `HomeController::attendanceWidget()`, a role the installer never seeds | Removed; only `Super Admin` sees everything. A custom role a school names "Admin" no longer gets that access |

`UploadService` (security fixes, S6) and `wiki/_Sidebar.md` (S4) are not part of this feature.

## User stories

- **School admin:** I can't install code from the admin panel, so a stolen session can't take over the server. Releases arrive by deploy, with new permissions already in place.
- **School admin:** the Fees configuration page shows only the gateway we can use (Stripe).
- **Parent and learner (apps):** the fees screen offers only gateways that work. An old app build that still knows Razorpay, Paystack or Flutterwave simply doesn't see them.
- **Finance:** payments made through the removed gateways still show in the transaction log with their gateway.
- **Deployer:** a fresh container install seeds email templates, and every start brings new roles and permissions to an existing install.

## Acceptance tests

| Given | When | Then | Test |
| --- | --- | --- | --- |
| Any visitor, a Teacher or the Super Admin | they open `GET`/`POST /system-update`, `/storage-link`, `/migrate`, `/seeder_install` or `/rollback` | 404 | `RemovedFeaturesTest` |
| The app is booted | the route list is built | routes come only from `bootstrap/app.php`; `route:list` equals the old list minus the 10 removed routes | `RemovedFeaturesTest::test_routes_are_registered_once`, route list diff in the self-test |
| A gateway calls `POST /webhook/razorpay`, `/webhook/paystack` or `/webhook/flutterwave`, or opens `/response/{paystack,flutterwave}/success` | the request arrives | 404 | `PaymentWebhookTest::test_removed_gateways_have_no_webhooks_and_their_old_transactions_still_read` |
| Transactions paid through gateways 1, 3 and 4 | finance opens the transaction log | each row keeps its `payment_gateway` and status | same test |
| A school whose settings still have `razorpay_status`, `paystack_status`, `flutterwave_status` = 1 | an app reads `GET /api/settings?type=app_settings` | `payment_options` has `stripe` and none of the removed gateways; no secret appears | `StudentApiSecurityTest::test_removed_gateways_are_neither_offered_nor_accepted`, `test_public_settings_do_not_include_payment_secrets` |
| The same school | a learner or parent posts `add-fees-transaction` with `payment_method` 1, 3 or 4 | `error: true`, `The selected payment method is not available.`; no transaction is created | `StudentApiSecurityTest::test_removed_gateways_are_neither_offered_nor_accepted` |
| Stripe events | they are signed, retried, for the wrong amount or currency, failed then paid, paid then failed | the existing webhook rules hold (record once, 400 on mismatch, a late failure does not undo a payment) | `PaymentWebhookTest` |
| A teacher who also has a custom role named "Admin" | they open the attendance widget | they see only their own sections | `AdminRoleTest` |
| A user whose only role is a custom "Admin" | `Students::ofTeacher()` runs | no students | `AdminRoleTest` |
| The container's seeding scripts | they name a seeder | the seeder exists under that exact class name | `DeploySeedersTest` |
| Every v1 endpoint the apps call | `ApiContractTest` runs | the 162 snapshots are unchanged | `ApiContractTest` |

## Data changes

None. No migration. `payment_transactions.payment_gateway` keeps 1 (Razorpay), 3 (Paystack) and 4 (Flutterwave) on old rows; `PaymentService::RETIRED` lists them so they are never reused. The `razorpay_*`, `paystack_*` and `flutterwave_*` rows stay in `settings` and are no longer read: they hold secrets and should be deleted when the keys move to Secret Manager. The `email_tempalates` table keeps its name.

## Endpoints

Removed (404): `GET|POST system-update`, `GET storage-link`, `GET migrate`, `GET seeder_install`, `POST webhook/razorpay`, `POST webhook/paystack`, `POST webhook/flutterwave`, `GET response/paystack/success` (`paystack.success`), `GET response/flutterwave/success` (`flutterwave.success`).

v1 unchanged in shape: `GET api/settings?type=app_settings` leaves the removed gateways out of `payment_options`, which is how it already answered for a gateway the school had switched off; `POST api/{student,parent}/add-fees-transaction` still validates `payment_method` as 1 to 4 and answers 1, 3 and 4 as not available. No field or route the apps use was renamed or removed.

## Screens

- Admin panel: the System Update sidebar item and page are gone. The Fees configuration page has one gateway tab, Stripe.
- Apps: no change in this stream. The app stream (S2) removes `razorpay_flutter` from `e-school`; installed builds keep working because the gateway is no longer offered.

## Notification types and module switch

None. Fees online payment stays behind the existing settings (`stripe_status`); the `fees` module switch arrives in Phase 1.

## Security notes

- Removing System Update closes "Super Admin session = code execution on the server".
- Maintenance commands no longer run from a GET link (CSRF-able by an image tag for a signed-in Super Admin).
- Two fewer third-party SDKs and one fewer VCS dependency fetched at build time.
- The webhook surface is one signed endpoint (Stripe).
- The "Admin" role check let any custom role named "Admin" see every student; removed.
- Deploy: the container now runs `InstallationSeeder` (which syncs the built-in roles' permissions and clears the caches) and `EmailSeeder` on every start, then caches the config again. Both are idempotent (`firstOrCreate`).

## Self-test steps

1. As the Super Admin, check the sidebar has no System Update item; open `/system-update`, `/storage-link`, `/migrate`, `/seeder_install`: each is a 404 page.
2. Open Fees > Fees Configuration: only the Stripe tab is shown; save with Stripe off and with Stripe on (currency `ZAR`).
3. In the student/parent app (or with curl), `GET /api/settings?type=app_settings`: `payment_options` lists only `stripe` when it is on.
4. `curl -X POST <host>/webhook/razorpay` gives 404; `<host>/webhook/stripe` without a signature gives 401.
5. Fees > Transaction Logs still shows Razorpay, Paystack and Flutterwave badges for old transactions.
6. `php artisan route:list --json` before and after: only the 10 routes above differ.
7. Start a container against an empty database: the log shows the three seeders and `Installed`; restart it: the log shows the seeders again and no error.
