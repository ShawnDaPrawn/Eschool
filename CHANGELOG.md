# Changelog

- Phase5: actual daily cash custody reports, retained physical count revisions,
  explained differences and private exports without automatic ledger adjustments.


- Phase5: original vendor settlement batches, separate Finance approval, exact
  school sales/VAT recognition, verified manual payouts and private vendor reports.


- Phase5: dedicated vendor operators, explicit counter assignments, private panel
  and native scanner access, single-use collection and interrupted handover recovery.


What changed in NavigateEd, newest first. Each phase release gets a section when it reaches production; work merged since the last release sits under **Unreleased**. Entries are grouped by who notices them (Admin panel, Apps, API, Platform, Security, Operations, Removed, Developers), and say in plain words what changed for the people who use or run the platform. Deployers' actions are marked **Deploy:**.

Earlier history is kept where it was written and is not repeated here:

- Upstream eSchool versions up to 3.5.0 (the codebase we bought): [`documentation/changelog/index.md`](documentation/changelog/index.md), published at https://shawndaprawn.github.io/Eschool/changelog/.
- This fork's work before NavigateEd Phase 0 (security hardening, tests and CI, dependency updates, translations, branding), with links to every commit and pull request: [`wiki/Release-History.md`](wiki/Release-History.md).

## Unreleased

### Added

- Platform: Owners now set the AI spending caps in HQ. In the AI control
  centre at `/saas/ai`, each AI feature's daily caps per school, per person and
  per learner can be changed with a reason, and apply from the next request.
  The values in `config/ai.php` are the defaults until HQ saves one. Every
  change is kept with who made it, when, the old and new amounts and why, and
  is in the HQ audit log. A school's own lower limits in its AI settings still
  apply on top (the lower of the two is used), and HQ never changes them.
  Amounts are in the gateway's metering currency (US dollars today), with no
  exchange rate applied. **Deploy:** run the migrations.
- AI: the Subject tutor is a personal service to the learner, not a school
  service. Tutor use no longer counts against a school's daily AI cap, and a
  school at its cap can still use the tutor. The tutor keeps its own daily
  caps per person and per learner, set in HQ. HQ shows tutor spend apart,
  labelled as personal.

- Platform: an AI control centre at `/saas/ai` for Owner, Operations and
  Support. It shows whether each AI feature and provider is qualified, its
  model, price and daily caps per school, person and learner; each school's AI
  spend this month and today against its daily cap; and how many safeguarding
  flags wait for each school's reviewers. Only settings, totals and counts are
  shown, never a prompt, an answer or a learner. Owners can switch an AI
  feature on or off for a school with a reason (kept in the audit log); a
  feature that is not qualified cannot be switched on. Amounts are in the
  gateway's metering currency (US dollars today), with no exchange rate
  applied. The dated cost report at `/platform/ai-costs` is unchanged and
  linked for staff who hold its grant.
  `engineering/specs/platform/hq-ai.md`.

- Caseload renewals for a new school year: in Early warning and interventions,
  **Renew caseloads for a new year** lists last year's open risk caseload grants
  for learners placed in the new year. A principal (or the learner's grade head)
  keeps the same counsellor, chooses another one, or lets the grant end. Ended
  grants are never carried, and only caseload administrators see the page.

- Notifications: school notice emails carry the school's crest, name and
  colour and a plain-text version, and school messages arrive in full in the
  email. Private notices (safeguarding, wellbeing, discipline, progress and
  similar) still say only that there is something to see in the app.
- Notifications: optional school notice emails have a one-click unsubscribe
  (in the email client and as a footer link) that switches that notice's email
  off. Required notices, such as absence alerts and urgent notices, never
  carry it.
- Communication: smart groups. Staff can save a group by rules (grades,
  homerooms, teaching and activity groups, any or all of them, learners or
  their guardians) that is worked out again whenever a message is previewed
  and sent. Saved audiences can now be private or shared. **Deploy:** run the
  migrations.
- HQ shows each school's health at `/saas/schools/{id}/health`: weekly active
  families, how many learners have a guardian the school can reach, sign-ins by
  role, module use, email bounces and complaints, push failures, AI spend against
  the month's cap and failed jobs, counts only and never a person. `/saas/health`
  compares every school from nightly figures, schools at risk first; the at-risk
  rules (activity down 30%, reach under 60%, bounces at 5% and others) are in
  `config/school_health.php`. Owner, Operations, Support and Finance can see it.
  **Deploy:** run the `school_health_snapshots` migration; the scheduler runs
  `platform:school-health` nightly (run it once by hand to fill the comparison).

- Platform: a breach and incident register at `/saas/incidents` for Owner,
  Operations and Support. Each incident records the schools it affects and
  when, how and whom at each school Navigate Ed told, whether the Information
  Regulator must be told and when it was, a timeline and a closing review. An
  incident that may involve personal information cannot close until every
  school has been told and the Regulator decision is recorded. **Deploy:** run
  the migrations.

- Notifications now reach every device a person is signed in on, not only the last
  one: a parent's phone and tablet, or a teacher's two devices, all receive them.
  Signing in to an app on another device no longer signs the first one out (up to
  five devices per app), and signing out ends only that device. **Deploy:** run the
  additive `user_devices` migration, which copies each existing push registration.
- Platform: an HQ audit log at `/saas/audit` shows who at Navigate Ed did what
  to which school, when and why (reasons, before and after values), filtered by
  school, action, staff member and date, and exported as CSV. A school's own
  history is never shown there, and each export is itself recorded.
- Admin panel: exam mode. Exam periods per grade (Exams and results → Exam
  periods) turn a learner's timetable days into exam days, leaving normal
  lessons out unless the school keeps them. Each exam sitting can have a venue,
  and learners and guardians are told when an upcoming sitting's date, time or
  venue changes, or it is removed. **Deploy:** run the migrations.
- Platform: platform staff roles (Owner, Operations, Support, Finance, Content,
  Sales read-only). Every HQ page and action checks the role. Owners invite
  staff by email from `/saas/staff`; the invited person chooses their own
  password. Access is time-limited and every change is audited with its reason.
  Existing grants became Owner. **Deploy:** run the migrations.

- Developers: school permissions are now described as modules with access levels
  (view, contribute, manage) and named special abilities, in
  `config/module_access.php`, as the foundation for a simpler role editor. No
  permission changes for anyone.

- Finance can record verified cash/EFT fee receipts, review allocation to explicit
  holder shares, and retain overpayments as credit. Parents have private fee
  statements showing their charges, applied payments and held credit separately.
  Legacy payment history remains separate; no payment gateway is activated.
  **Deploy:** rehearse the additive payment migration and reconciliation on isolated
  staging, explicitly assign `fee-payments-manage` to real Finance roles, and
  confirm the school allocation rule before allocating.

- The learner's LURITS number can be recorded as their primary identifier, once per
  learner across all schools, encrypted, and never written twice: a number another
  learner already holds opens a conflict for review instead. The NavigateEd number
  remains the temporary identifier for learners without one (ADR 0007). Screens,
  API fields, imports and SA-SAMS keying follow. **Deploy:** run the additive
  `learner_identifiers` LURITS migration.

- Internal school ledger foundation with balanced immutable integer-cents postings,
  permanent receipt identities, protected wallet balances and nightly reconciliation.
  Existing receipt history stays intact; this does not activate wallets or payment providers.
  **Deploy:** rehearse the additive migration (including trigger privileges) and
  `ledger:reconcile --json` on isolated staging; route critical reconciliation logs
  to operations before real money uses the ledger.

- The new-year wizard can carry teaching groups and activity groups (teams, clubs,
  societies) into the next year empty, with their tree, rules and seasons moved to
  the new year, so learners and staff are allocated afresh and nobody is left in
  last year's groups.

- Learner Home and parents' child academic screens remain accessible with unpaid
  fees, including saved links to the former fee-lock screen. Guardian fee privacy
  and approval checks continue to apply.

- Short days: the timetable workbench can replace a bell schedule on one date
  with short-day bells. Lessons keep their order and take the short times;
  lessons past the last short period do not run that day. Clashing short days
  are refused, and affected learners, guardians and teachers are notified. The
  v2 week adds an optional `day_pattern`. **Deploy:** run the additive
  `bell_schedule_overrides` migration.

- **Platform admin area (`/saas`), first version.** Platform staff can see every school with its package and counts of learners, parents and teachers, set up a school with its first administrator (a one-time password, shown once), change its package and modules, and suspend, archive or restore it with a reason. It reads no person's record and is closed to school administrators. Opening it needs both a platform staff identity and an expiring grant set from the operator console. Support access into a school, imports and usage/health views are not part of this version. **Deploy:** run the migration, then `php artisan platform:staff-identity <user_id>` and `php artisan platform:admin-access <user_id> --hours=8` for each operator.

- Staff invitations: a new school's first Super Admin is emailed a one-use
  link to choose their own password instead of being handed a one-time
  password, and the platform can send it again until it is accepted. School
  staff can send a colleague a sign-in invitation from their profile.
  Accepting verifies the email address and signs out every other session.
  **Deploy:** run the additive `staff_invitations` migration; mail must be
  configured for the emails to arrive (the link is also shown once).

- Manage dated teaching groups, learner memberships and teacher/homeroom/leadership assignments with explicit class-grade mappings, audited corrections and current allocation checks for existing app projections.

- HQ school records: platform staff keep each customer school's stage (lead to
  live, at risk, churned), profile, contacts, contracts with the POPIA operator
  agreement, and a timeline of notes, on a new page in `/saas`. The school list
  shows each school's stage. Schools never see it, and it shows none of a
  school's people. **Deploy:** run the additive `school_accounts` migration.

- Prepare versioned curriculum topics and term assessment plans with exact task/question maxima, separate term and annual weights, allocated-teacher access, audited publication and explicit new-year draft copying.

- Guardian management: the school office can give a learner any number of
  guardians, each with a relationship and the rights to approve and to see
  fees, from "Manage guardians" on the learner's profile. A learner always keeps
  at least one approving guardian, and removing a guardian ends their access at
  once. **Deploy:** run the additive `guardian_links` migration.

- Add versioned CAPS/IEB subject reference imports and school subject mapping, with source provenance, explicit publication, tenant/permission checks and a Material 3 catalogue page.

- Role editor by module: school administrators can create and edit custom
  roles by choosing a level (no access, view, contribute, manage) for each
  module that is switched on, plus named abilities. Sensitive abilities need a
  confirmation and are audited. The old checkbox editor is still available.

- School staff can edit app contacts and branding under Settings → School information, publish PDF policies and schedules, and show current-year term dates in both apps’ My school screen. School selection is explicit on each request. Unlisting removes a publication from the screen while its public link remains valid.

- Role templates: the role editor can start a new role from nine templates
  for common school jobs (principal, deputy, office, bursar, head of
  department, grade head, activities coordinator, reception, IT). Sensitive
  abilities are never part of a template.

- Add school-specific person profile categories, protected defaults, encrypted custom values and authorised API v2 forms/configuration. Partial edits validate supplied fields without blocking on unrelated incomplete fields. School admins can arrange fields in Settings, and authorised people use the same form metadata in the panel and both apps. Saves update changed fields only. Permitted CSV exports filter sensitive values before decryption; private drafts stay out of browser/session storage. Private file fields accept encrypted PDF/PNG/JPEG attachments with authenticated previews and preserve unrelated edits. Legacy migration remains in progress. **Deploy:** preserve APP_KEY; rehearse the additive profile migration and back up profile values, attachment metadata and private ciphertext before rolling it back.

- Who can see what: a read-only page shows one person's or one role's level in
  each module, their abilities (sensitive ones marked), any stray permissions,
  and the pages in their menu, exactly as that person's own sidebar for a
  person.

- People can choose a global handle in the shared app settings. Learners under 18 need a verified guardian to approve the proposal; adults choose directly. Handles avoid personal identifiers, changes are limited per term, and old names remain reserved for 90 days. Approved handles work alongside existing sign-in names. Existing app/API responses remain compatible.

- Product catalogue: the 44 module switches are grouped into ten product
  modules with a readiness level and a price placeholder, shown to platform
  staff at `/saas/packages`, and a new `pilot` package includes them all.

- School admins can control included features under Settings → School modules. Disabled features disappear from navigation and app context, and their routes refuse access. Required features stay on; fees with retained records become read-only. Existing schools retain their seed features, while new explicitly packaged schools start fees off. Native apps refresh on a queued school-specific context change.

- Family reach: a page for the school office shows how many current learners
  have a guardian the school can reach in the parent app or by verified email,
  reach by homeroom, and which learners to follow up.

- Lifelong student numbers work alongside existing learner logins. School staff can append dated homeroom moves through API v2, while installed clients keep their existing session fields. Parent context includes each authorised child's school, approval, number and homeroom. **Deploy:** rehearse `people:backfill-identities --queue` on isolated staging before production acceptance; historical dates imported from the old session table are marked as legacy-derived. Permanent cleanup preserves numbers and other schools' accounts, and refuses learners with dated enrolment history.

- Guardian access approvals and one-use emailed invitations, including email verification, withdrawal, separate fee access and versioned consent. Existing family relationships are migrated without inventing approval. The shared approval screen uses generated API models.

### Developers

- **Web app preview** (Actions > Web app preview): builds the learner and parent app and the teacher app for the browser and puts them on Firebase Hosting preview channels, so both can be opened and installed on a phone as web apps before a store or production release. The job summary gives each address and says whether the chosen server accepts it. PR runs save downloadable artifacts without Firebase credentials. Publishing requires an explicit `deploy_preview` dispatch from `main`, defaults to staging and refuses the configured production API. The live portals are unchanged. **Deploy:** on a server running this code, add both preview addresses to `CORS_ALLOWED_ORIGINS` with the portal addresses before signing in there.

- Narrow Phase 1 closeout to supported clients after the product owner confirmed there are no legacy clients. Legacy profile storage retirement and compatibility writes are no longer release prerequisites; existing data remains subject to privacy controls.

- Integrate dependency PRs #48, #49, #50, #53, #55, #56, #57, #59 and #60, including their compatibility migrations. Pin Flutter CI and deployment builds to 3.47.7 / Dart 3.13.5 for Google Fonts 9; preserve the Material type scale and refresh all affected lockfiles. File Picker 13 supports both apps' attachments and single-file uploads. TeX 5 starts its renderer, keeps question and answer views independent, and loads its bundled browser bridge; release phones allow its device-local asset server. Image 4 supports all legacy image helpers as well as tenant uploads. Add regression checks for image encoding, school role revocation, checkout setup/cancellation, typography and rendered exam maths.

### Security

- Private group conversation drafts keep their conversation type, audience and private reply target after validation errors. Destinations are checked again before retaining text; unavailable targets discard the draft rather than selecting a broader audience. Reply drafts are not copied into the new-conversation form.

- Pin the shared web, worker and scheduler image and PHP CI jobs to PHP 8.3.36 for CVE-2026-109618 and CVE-2026-109619 (PHP2Shell). CI now verifies the built runtime and Apache configuration. **Deploy:** rebuild and replace all three roles; existing containers remain vulnerable until updated. See `engineering/runbooks/deploy.md`.

Phase 0 (Groundwork): built on the phase branch, not yet released to production. Nothing changes for teachers, learners and parents on their phones; the admin panel's navigation and school-year tools are new, and the platform underneath is ready for Phase 1.

### Admin panel

- Navigation is arranged by job (Home, People, Academics, Communication, Money, Activities, Wellbeing, Settings) in plain words. Every page keeps its address.
- Home shows a setup checklist worked out from the school's data, and today's numbers.
- Global search in the header ("/" or Ctrl/Cmd+K) finds learners, parents, staff, homerooms, subjects and settings pages.
- The year switcher in the header shows past years read-only: a banner on every page, write buttons disabled, and the server refuses changes.
- "Start the new school year" creates next year, copies what you choose (terms, subjects and their teachers, the timetable) and makes it current in one step.
- The header, navigation and new page components are built with the Material 3 library instead of Bootstrap.
- The teacher list has Deactivate and Activate; a deactivated teacher is signed out everywhere and cannot sign in.
- The Email Configuration page's "Amazon SES" choice now works.
- Fixed: the staff list did not load when a staff member had no date of birth.

### Apps

- iOS builds of both apps (and the teacher app's facilitator and vendor builds) take their version and build number from `pubspec.yaml`, as Android does. Every iOS build used to report build number 3, so App Store Connect would refuse the second upload, and force update compared against the wrong build.
- Both apps read the server address and environment from build settings (`--dart-define` `API_BASE_URL`, `ENV`, `FLAVOR`, or `config/staging.json` and `config/production.json`) instead of `lib/utils/constants.dart`.
- Shared code, sign-in and notifications moved into the packages `eschool_core`, `eschool_session` and `eschool_notifications`; nothing changes on phones.
- Tablet and desktop layouts: a navigation rail and a centred content column. Routes take typed arguments.
- Portal addresses and `navigateed://` links open the matching screen after sign-in.
- Web builds of both apps, for `app.navigateed.co.za` (learners and parents) and `teach.navigateed.co.za` (teachers). Uploads, payments and push notifications are not available in the browser yet.
- The web portals sign in with the panel's session cookie (no token in the browser) and show pictures and PDFs from the panel; a browser that reports an invalid language opens in English instead of a blank page.
- Teacher app: the take-attendance button and each lesson on Schedule are read out by screen readers.
- The fees list shows the learner's homeroom ("Grade 5 A") instead of "Grade".

### API

- `/api/v2` in the same `{error, message, data, code}` envelope, starting with `GET /api/v2/me/context`: the user, roles, current school, switched-on modules, notification rules, feature flags, sign-in methods and children.
- Named rate limiters (`api`, `auth`, `money`) and an `Idempotency-Key` header for v2 writes.
- `admin-panel/openapi.yaml` (OpenAPI 3.1) is the contract: linted in CI, every v2 response is validated against it, and `packages/eschool_api` is generated from it.
- API responses now carry the security, rate-limit and CORS headers (`ResponseService` returns its response instead of exiting). The v1 API is unchanged: the 162 response snapshots still match.

### Platform

- Mail and push notifications are sent by queued jobs with retries, and every delivery attempt is recorded.
- Transactional email goes through Amazon SES in Cape Town; bounces, complaints and deliveries come back from SNS and are recorded.
- A domain-event outbox, dispatched every minute.
- Audit log for users, learners, marks, fee payments and settings.
- Money is stored and computed in integer cents beside the old float columns, which keep working for v1 (back-filled daily by `money:backfill-cents`).
- Settings can carry a school id, ready for Phase 1.
- Structured JSON logs (`LOG_CHANNEL=json`) to stderr with a request id, user id, school id and route on every line, an `X-Request-Id` response header, and exceptions formatted for Cloud Error Reporting. Log context carries ids only, never request bodies; e-mail addresses, ID numbers and phone numbers in messages are masked.
- `GET /up/worker`: a health check that fails when a queued job has waited more than ten minutes. A job that uses up its tries is logged once as "Queued job failed" (by name, without its data), and log lines written by a job carry its name and id.
- Container roles: the one Docker image runs as the web service (default), the queue worker (`CONTAINER_ROLE=worker`: `queue:work --tries=3 --timeout=120`, restarted hourly, stopped gracefully on SIGTERM), the scheduler (`CONTAINER_ROLE=scheduler`: one `schedule:run`, for a Cloud Run Job started every minute) or a one-off `artisan` command. Only the web role migrates (one instance at a time, behind a MySQL lock) and runs the installer seeders, `InstallationSeeder` and `EmailSeeder`, on every start.
- The database queue's `retry_after` is 180 seconds, longer than the worker's 120-second job timeout, so a slow job is not picked up twice.
- PHP no longer puts function arguments into exception traces (`zend.exception_ignore_args`). The image adds the `pcntl` extension, which `queue:work` needs to enforce job timeouts and stop cleanly.
- Routes are registered once.
- Fixed: fresh container installs failed on the `EmailSeeder` name.

### Security

- One password rule everywhere: at least 10 characters, letters and numbers, and not found in known breaches.
- Deactivated teachers are signed out and cannot sign in.
- App tokens are named per app; signing in signs out the same app's other devices, and a password change revokes every other session.
- CORS is limited to the panel and the web portals.
- Web portals use Sanctum's cookie mode: a portal sign-in starts a new session instead of issuing a token, a refused sign-in keeps no session, signing out ends it, and a password change elsewhere ends it. The panel no longer encrypts its cookies twice (everyone signs in to the panel again once after the deploy).
- Uploaded files under `/storage` send CORS headers to the panel and portal origins only (`GET` and `HEAD`, `Vary: Origin`).
- New learners' passwords are set by a guardian through an e-mailed link or a printed one-time code; passwords that were a date of birth are flagged for a change.
- Uploads are stored per school, client file names are sanitised, and files whose content does not match their type are refused.
- A report-only content security policy on the panel, with violations reported to `/csp-report`.
- Secrets are read from Secret Manager (environment variables) instead of `settings` rows; `php artisan secrets:audit-settings --purge` clears the old rows.
- Failing requests are logged without their values.
- A custom role named "Admin" no longer sees every learner.
- Secret scanning with gitleaks over the whole history on every pull request, push to `main` and weekly: no credentials found. The Firebase Android API keys in `google-services.json` are public client identifiers and are allowlisted for that file and key format only; the product owner should confirm their restrictions in the Cloud Console (`engineering/security/phase-0.md`).
- Dependabot for Composer, pub (both apps and every package), npm (docs and the panel's Playwright project), Docker and GitHub Actions. The dependency audit also lists outdated Dart packages (`dart pub outdated`, informational). Threat model and the Phase 0 security record in `engineering/security/`.

### Operations

- Environments on Google Cloud: `infra/gcp/provision.sh staging|production` creates or updates Cloud SQL (backups; point-in-time recovery in production), the uploads bucket, Secret Manager secrets (names only), least-privilege service accounts, the Cloud Run services `navigateed-admin` and `navigateed-worker`, the `navigateed-scheduler` job with an every-minute Cloud Scheduler trigger, Workload Identity Federation for GitHub Actions, uptime checks and alerts. It gives the panel, the worker and the scheduler job the SES settings and keys and the secrets that left the `settings` rows, each mounted once it has a value. Not yet run against a real project.
- Deploy workflows: staging on every merge to `main` once the repository variable `STAGING_DEPLOY_ENABLED` is `true`; production from a `v*` tag after a reviewer approves, with a database backup first and smoke tests after. Both deploy the documentation and the two web portals to Firebase Hosting.
- `SESSION_DOMAIN=.navigateed.co.za` for staging and production, applied by `provision.sh` once `APP_URL` is on that domain; until then the deploy workflow builds the portals with `WEB_AUTH_MODE=bearer` (the `WEB_AUTH_MODE` environment variable overrides).
- Runbooks for environments, deploy, release, rollback, restore (with the restore drill, not yet run) and incidents in `engineering/runbooks/`.
- Local stack with Docker Compose at the repository root: MySQL 8, the panel, the worker and the scheduler, built from the same image as Cloud Run.
- k6 smoke test (`engineering/perf/k6-smoke.js`) with our p95 budgets (Build Bible 5.2) (300 ms for lists, 150 ms for `/api/v2/me/context`), run by hand from **Actions > k6 smoke**.

### Removed

- The in-panel System Update and the `storage-link`, `migrate` and `seeder_install` URLs (the container does these at start).
- The Razorpay, Paystack and Flutterwave gateways, from the panel and from the learner and parent app. Stripe stays, the v1 API is unchanged, and old transactions still show their gateway.
- The unused `EnsureUserIsTeacher` middleware.

### Developers

- `engineering/`: phase plans and questions, feature specs, decision records (ADR 0001 to 0005), runbooks, the security record, the self-test record and the living appendices; and this changelog.
- Demo account credentials are omitted from this public changelog.
- Playwright checks of every panel page at 1280 and 1024 px with axe, the main flows and the removed URLs, in CI.
- Test suites for tenancy (every API route), the role matrix (every route against every role) and the OpenAPI contract (every v2 response).
- CI builds both apps for the web and runs their smoke flows on an Android emulator and in Chrome.
- The `EmailTempalate` model is renamed `EmailTemplate` (the table name is unchanged).

**Deploy:** deploy the worker (`navigateed-worker`) and the scheduler job with this release: mail and push are queued now. Run `infra/gcp/provision.sh` for the environment, which sets `LOG_CHANNEL=json`, `CACHE_DRIVER=database`, `QUEUE_CONNECTION=database`, `MAIL_MAILER=ses` and the SES settings, and creates the secrets; add the SES keys and the secrets that left the `settings` rows to Secret Manager, re-run the script, then run `php artisan secrets:audit-settings --purge` (`engineering/runbooks/release.md`, step 7). See `engineering/runbooks/environments.md`.

### Phase 5 fee corrections

- Finance can review and post an immutable credit note against a recorded invoice
  share, reducing debt or restoring paid money to the original receipt's held
  credit while retaining holder and learner restrictions.
- Finance and private parent statements show original charges, credit notes and
  net balances; private Finance explanations remain excluded from parent reads.

### Phase 5 fee items

- Finance can maintain additional learner/family fee items and review one-off
  occurrence invoices with exact payer shares and duplicate protection.
- Family charges bill once, retain real learner visibility and work with existing
  credit notes, held payments and learner pins. Tuition-first allocation now
  distinguishes annual tuition from additional items.

- Phase5: personal wallets, private statements and guardian controls independently
  of fees, with explicit Finance settings and family/teacher/facilitator app access.

- Phase 5: verified school-owned master wallet funding, private printable receipts
  and shared outside receipt identity across fee and wallet collections.

- Added independently confirmed operator cash handovers, custody reservations, physical shortage limits and nightly cash reconciliation; fee, wallet and vendor cash payouts use the actual operator’s custody.
