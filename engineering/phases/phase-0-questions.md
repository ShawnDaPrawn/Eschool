# Phase 0: open questions and defaults taken

The questions Phase 0 had to settle, and the ones that came up while building it. Until the product owner answers, engineering works to the default below. Each default is recorded as a decision record where it shapes code.

| # | Question | Default taken | Where it lands | Status |
| --- | --- | --- | --- | --- |
| Q0.1 | Which provider sends transactional mail, and from which domain | Amazon SES in `af-south-1` (Cape Town), so mail data stays in South Africa; Laravel's `ses` mailer; bounces and complaints arrive through SNS to `POST /webhook/mail/ses`. Sending domain `mail.navigateed.co.za` with SPF, DKIM and DMARC (`p=quarantine`). The mailer stays switchable through `MAIL_MAILER`, so SMTP keeps working for schools that self-host. | [ADR 0004](../adr/0004-transactional-email.md) | Defaulted |
| Q0.2 | Portal hostnames | `app.navigateed.co.za` (learner and parent web portal), `teach.navigateed.co.za` (teacher portal), `admin.navigateed.co.za` (panel and API). Staging prefixes each with `staging-`. | [ADR 0003](../adr/0003-app-packages-and-web.md), CORS, Sanctum stateful domains | Defaulted |
| Q0.3 | Which South African payment gateway to try first in a sandbox (built in a later phase) | Peach Payments: cards, instant EFT, PayShap and hosted checkout behind one integration. | Recorded only; no code in Phase 0 | Defaulted |
| Q0.4 | Cloud SQL tier | Production `db-custom-2-7680` (2 vCPU, 7.5 GB) with high availability, automated backups, 7-day point-in-time recovery; staging `db-g1-small` without HA. | [runbooks/environments.md](../runbooks/environments.md) | Defaulted |
| Q0.5 | Student number format (Phase 1 uses it) | Ten digits: two-digit year of first enrolment at any NavigateEd school, a seven-digit platform sequence, and a Luhn check digit (for example `2600012344`). No school code, name or birth date inside it, so it never changes when a learner moves school. | Phase 1, [ADR 0005](../adr/0005-student-number-and-handles.md) | Defaulted |
| Q0.6 | From what age a learner sets their own handle (Phase 1 uses it) | POPIA treats everyone under 18 as a child: a learner under 18 can propose a handle but it takes effect only after an approving guardian accepts it; 18 and over sets it directly. Handles are 3 to 20 characters, lower-case letters, digits, dot and underscore, and may not contain the learner's surname. | Phase 1, [ADR 0005](../adr/0005-student-number-and-handles.md) | Defaulted |
| Q0.7 | Branch naming | The convention is one branch per feature (`feat/p<N>-<slug>`); this run's tooling assigns one branch per session, so Phase 0 and Phase 1 each ship as one pull request from that branch, built from internal work-stream branches. | This file | Noted |
| Q0.8 | How the v1 API shows the removed gateways (S1) | Left out of v1 `payment_options`, the way v1 already shows a disabled gateway; no field renamed or removed | `ApiController` settings; snapshots unchanged | Defaulted |
| Q0.9 | What happens to the removed gateways' settings rows (S1) | Kept in the database until `secrets:audit-settings --purge` (S6) clears every `razorpay_*`, `paystack_*` and `flutterwave_*` row | Release runbook, step 7 | Defaulted |
| Q0.10 | When the installer seeders run in a container (S1) | `InstallationSeeder` and `EmailSeeder` run on every start of the web role (they are idempotent) | `docker/entrypoint.sh` | Defaulted |
| Q0.11 | Staging's API address (S2) | `https://staging-admin.navigateed.co.za` | `config/staging.json` in both apps | Defaulted |
| Q0.12 | Desktop layout of the apps (S2) | Desktop above 1024 px; content in a centred 640 px column beside a navigation rail | `eschool_core` `Breakpoints`, `ContentColumn` | Defaulted |
| Q0.13 | How the web portals sign in (S2) | Sanctum cookie mode by default (the Build Bible, section 3.4); `WEB_AUTH_MODE=bearer` switches a build back to tokens. The server side of cookie mode is still open (self-test defect D1) | ADR 0003, `eschool_session` | Defaulted |
| Q0.14 | Which Hosting site serves the docs (S2) | The project's default site (`navigateed-d8c06` in production, `navigateed-staging` in staging); `FIREBASE_SITE_DOCS` overrides it | `.firebaserc`, `infra/firebase/deploy-hosting.sh` | Defaulted |
| Q0.15 | Which school years are read-only in the panel (S3) | Every year other than the current one, including an upcoming year until it is made current | `EnforceReadOnlyYears` | Defaulted |
| Q0.16 | How the read-only guard decides (S3) | It refuses every write in a read-only year except the routes on an allow-list (`panel.read_only_years.allow`), so a new route is safe by default | `config/panel.php` | Defaulted |
| Q0.17 | Where pages without an obvious section go (S3) | Holidays and Promote learners under Academics > School year; learner leave under Wellbeing; staff leave under People | `config/navigation.php` | Defaulted |
| Q0.18 | The finance profile in the demo school (S3) | A custom role named "Finance" with the fee permissions, as a school would create it | `DemoSchoolSeeder` | Defaulted |
| Q0.19 | The confirm dialog's accessible role (S3) | `alertdialog` | `resources/views/components/page/confirm-dialog.blade.php` | Defaulted |
| Q0.20 | When a queued mail reads its template (S5) | When the mail is built on the worker, so a template edited after queueing is used | `App\Jobs\Platform\SendMail` | Defaulted |
| Q0.21 | How contact-form attachments reach the worker (S5) | Inside the job payload, which is encrypted (`ShouldBeEncrypted`) | `SendMail` | Defaulted |
| Q0.22 | What happens when writing the audit log fails (S5) | The failure is logged and the change goes ahead; auditing never blocks a write | `Auditable` | Defaulted |
| Q0.23 | How instalment amounts are rounded in cents (S5) | Shares stay exact until a single half-up rounding to cents | `FeesPaymentService` | Defaulted |
| Q0.24 | Whether `DispatchOutbox` runs may overlap (S5) | They may: row locks make overlapping runs safe, so the job is not unique | `App\Jobs\Platform\DispatchOutbox` | Defaulted |
| Q0.25 | Where v1's money values come from (S5) | Derived from the cents columns; the v1 snapshots are unchanged, and values are now exact where the float columns had rounded them | `Money`, v1 controllers | Defaulted |
| Q0.26 | The legacy mail failure log (S5) | `MailService`'s old failure log keeps the address for now (`LogRedactionTest` records it); removed with `MailService` | Follow-up in the build log | Defaulted |
| Q0.27 | How long a learner's set-up link and printed code stay valid (S6) | Link 7 days (`LEARNER_INVITATION_LINK_DAYS`), printed one-time code 30 days (`LEARNER_SETUP_CODE_DAYS`) | `config/auth.php` | Defaulted |
| Q0.28 | What signing in does to other devices (S6) | Signing in signs out the same app's other devices; a password change signs out every other session | `AppTokens` | Defaulted |
| Q0.29 | Which browser origins may call the API when none are configured (S6) | The panel plus the Q0.2 portal hostnames for that environment (`staging-` prefixed on staging); localhost on any port only for local and testing installs | `config/cors.php`, `CORS_ALLOWED_ORIGINS` | Defaulted |
| Q0.30 | When Cloud Run mounts a secret that is created empty (S8) | Only once it has a value, because Cloud Run refuses a revision that mounts a secret without a version; the script says which are missing | `infra/gcp/provision.sh` | Defaulted |
| Q0.31 | Which roles read the secrets that left the `settings` rows (S8) | The panel all of them; the worker the SES keys, the SMTP password and the Firebase account; the scheduler job, which is also the one-off artisan runner, the same as the panel so `secrets:audit-settings --purge` sees what the panel sees | `infra/gcp/provision.sh`, [environments.md](../runbooks/environments.md) | Defaulted |
| Q0.32 | The SES configuration set names (S8) | `navigateed-staging` and `navigateed-production`, created with the AWS account | `infra/gcp/env/*.env` | Defaulted |

## Questions Phase 1 depends on

Q0.1 to Q0.6 are the questions the Build Bible (section 6) asks Phase 0 to settle for Phase 1. All six have a default above and wait for the product owner's answers. Questions that belong to Phase 1 itself go in `phase-1-questions.md`.
