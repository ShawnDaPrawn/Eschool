# Appendix E: build log

What each phase delivered, what it deferred and why, and which of our questions are still open. Updated when a phase closes; a row is added when a phase starts.

| Phase | Status | Started | Finished | Delivered | Deferred | Open decisions |
| --- | --- | --- | --- | --- | --- | --- |
| 0 Groundwork | Built; gate open | 2026-10-21 | | Queue, scheduler and worker; mail through SES with delivery records; outbox; audit log; money in cents; API v2 with `/me/context` and `openapi.yaml`; web builds of both apps; panel navigation by job, Home checklist, search, year switcher, new-year wizard; security fixes; staging and production definitions, deploy workflows and runbooks; CI gates. [Details](#phase-0-delivered) | Everything that needs the cloud project or people (staging, production changes, the restore drill, SES, seven days of the worker, UX scripts, the demo), the web portals' sign-in from their own origin, and the follow-ups below. [Details](#phase-0-deferred-with-reasons) | Q0.1 to Q0.6 defaulted and waiting for the product owner; Q0.8 onwards defaulted by the streams ([phase-0-questions.md](../phases/phase-0-questions.md)) |
| 1 | Not started | | | | | |

## Phase 0 delivered

Built on the phase branch by streams S1 to S8 between 2026-10-21 and 2026-10-22; the changelog's "Unreleased" entry lists it for users and deployers, and each feature's spec is in [`engineering/specs/`](../specs/README.md).

- **Removals (S1):** System Update, the GET maintenance URLs, Razorpay, Paystack and Flutterwave, `EnsureUserIsTeacher`, the duplicate route registration; `ResponseService` returns instead of exiting (162 v1 snapshots unchanged).
- **Apps (S2):** `AppConfig` from build settings, `eschool_session`, `eschool_notifications`, shared code in `eschool_core`, typed routes, phone, tablet and desktop layouts, web builds of both apps, Hosting targets, smoke flows on Android and in Chrome.
- **Admin panel (S3):** navigation by job from `config/navigation.php`, the Material 3 page components, Home checklist and today's numbers, global search, a year switcher that opens past years read-only, the new-year wizard, `DemoSchoolSeeder`, Playwright with axe over every panel page at 1280 and 1024 px.
- **Operations (S4):** container roles (web, worker, scheduler, artisan), `infra/gcp/provision.sh`, staging and production deploy workflows, structured JSON logs, `/up/worker`, runbooks, gitleaks, Dependabot, k6 smoke, ADRs 0001 to 0005.
- **Kernel (S5):** queued `SendMail` and `SendPush` with `notification_deliveries`, Amazon SES with the SNS feedback webhook, `domain_events` and `DispatchOutbox`, `audit_log`, `Money` and the `*_cents` columns, `settings.school_id`.
- **Security (S6):** one password rule, teacher status, per-app tokens, CORS limited to the panel and portals, learner invitations, uploads per school with sanitised names, report-only CSP, secrets out of `settings` rows.
- **API v2 and test infrastructure (S7):** `/api/v2` with `GET /me/context`, rate limiters, `Idempotency-Key`, `openapi.yaml` and its contract suite, `packages/eschool_api`, the tenancy scaffold and the role matrix.
- **Close (S8):** the changelog, these appendices, the questions file, the security record, a local run of the self-test matrix, the wiki sweep, Cloud Run wiring for the SES and settings secrets, and the `/staff` fix.

### Exit gate

Our own reading of what closing Phase 0 takes (the Build Bible, section 6, has the gate itself).

| What has to be true | Status |
| --- | --- |
| Environments: a production backup and a successful restore drill; staging running, with the worker and scheduler up for a week and no unhandled failed job | Open: needs the staging project and credentials ([environments.md](../runbooks/environments.md), "One-time setup by the product owner") |
| CI: the new gates pass on three merges in a row | Open: counts from the first merges to `main`. Pint over the whole panel and Larastan level 1 are applied by the lead at the close |
| UX: the scripted acceptance paths pass, and new users get through most tasks of the UX scripts on each profile | Scripted acceptance paths pass locally ([self-test record](../self-tests/phase-0.md)); UX scripts open (staging, new users); the web portals blocked by defect D1 |
| Security record complete | Open: 9 (restore drill), 6b (secret values and purge) and 7b (Dependabot on `main`) wait for the environment; D1 and D2 found at the close ([security record](../security/phase-0.md)) |
| Questions Phase 1 depends on have an answer or a default | Done: all defaulted (Q0.1 to Q0.6), waiting for the product owner's answers |
| Product owner demo on staging | Open: needs staging |

## Phase 0 deferred, with reasons

Needs the cloud project, accounts or people:

- Provision and deploy staging (`provision.sh` written and dry-run; no Google Cloud credentials in this run), then production's Cloud SQL tier, high availability and point-in-time recovery, and the restore drill (needs both environments).
- Seven days of the worker and scheduler on Cloud Run without an unhandled failed job.
- Amazon SES: the AWS account, the `mail.navigateed.co.za` identity with SPF, DKIM and DMARC, production access, the configuration set and SNS topic, and the keys in Secret Manager (ADR 0004).
- Values for the secrets that left the `settings` rows, then `secrets:audit-settings --purge` on staging and production; revoke the removed gateways' keys.
- Firebase Hosting sites, DNS and custom domains for the portals and docs; the load balancer for `admin.navigateed.co.za`.
- Dependabot and its security updates (active once merged to `main`; a repository setting for the product owner); confirm the Firebase API key restrictions.
- The self-test matrix on staging and on a real phone (run locally only), the UX scripts with new users, the k6 smoke and performance budgets on staging, the CSP report-only walk on staging, the product owner's acceptance and the demo.

Defects and follow-ups found in Phase 0, carried into Phase 1's plan:

- **Web portal sign-in (D1, blocks the gate):** portal origins are Sanctum stateful, so bearer builds fail the CSRF check, and cookie mode has no server-side session sign-in yet. Build the cookie-mode sign-in, or take the portal origins out of the stateful list and build the portals in bearer mode until then.
- **Pictures and PDFs in the portals (D2):** `/storage` needs CORS headers for the portal origins.
- **Portal hosting:** a Hosting CSP that allows what Flutter needs (`wasm-unsafe-eval`, CanvasKit from `www.gstatic.com`, PDF.js from jsDelivr), or self-hosted copies; native App Links and universal links; browser uploads, payments and web push (Phase 1).
- **Apps:** the learner fees screen still lists Paystack and Flutterwave (disabled, as the server omits them); the fees list shows "Class : Grade" (D5); teacher accessibility gaps (D4); the `pdfx` plugin is untested on a real device; the `html <0.15.7` pin; the smoke flows find buttons by English text; guard against an invalid platform locale on the web.
- **Panel:** older pages' icon-only actions without an accessible name (listed in `admin-panel/e2e/allowlist.json`), fixed as each page moves to the page pattern; the fee settings screenshot in the user docs (`documentation/static/images/admin/13.png`) still shows the removed gateways' tabs; the unused key `currency_not_supported_by_razorpay`.
- **Kernel:** mark bounced or complained addresses undeliverable (needs a user column); clear `UNREGISTERED` FCM tokens; the old `MailService` failure log still writes the address; idempotency keys for the legacy v1 and panel money writes (with the ledger work in a later phase); give the new kernel tables their `school_id` values (Phase 1).
- **Payments:** pending payments started on a removed gateway are settled by hand.
- **Tests:** the tenancy suite asserts only that each route exists until Phase 1 brings a second school; `DemoSchoolSeeder` needs an exam paper and an assignment so marks and assignments can be self-tested from the seed.

Product-owner decisions are in the private Build Bible; the defaults engineering takes until the product owner answers are in each phase's questions file.
