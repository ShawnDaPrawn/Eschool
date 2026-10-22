# Appendix E: build log

The living copy of the Build Bible's Appendix E. Updated when a phase closes: what shipped, what was deferred and why, and which decisions are still open.

| Phase | Status | Started | Finished | Delivered | Deferred | Open decisions |
| --- | --- | --- | --- | --- | --- | --- |
| 0 Groundwork | Built; gate open | 2026-10-21 | | Queue, scheduler and worker; mail through SES with delivery records; outbox; audit log; money in cents; API v2 with `/me/context` and `openapi.yaml`; web builds of both apps; panel navigation by job, Home checklist, search, year switcher, new-year wizard; security fixes; staging and production definitions, deploy workflows and runbooks; CI gates. [Details](#phase-0-delivered) | Everything that needs the cloud project or people (staging, production changes, the restore drill, SES, seven days of the worker, UX scripts, the demo), the web portals' sign-in from their own origin, and the follow-ups below. [Details](#phase-0-deferred-with-reasons) | Q0.1 to Q0.6 defaulted and waiting for the product owner; Q0.8 onwards defaulted by the streams ([phase-0-questions.md](../phases/phase-0-questions.md)) |
| 1 SaaS platform and identity | Not started | | | | | |
| 2 Academic core and pilot | Not started | | | | | |
| 3 AI learning and pastoral | Not started | | | | | |
| 4 Facilitator and community | Not started | | | | | |
| 5 Money and commerce | Not started | | | | | |
| 6 Growth and ecosystem | Not started | | | | | |

## Phase 0 delivered

Built on the phase branch by streams S1 to S8 between 2026-10-21 and 2026-10-22; the changelog's "Unreleased" entry lists it for users and deployers, and each feature's spec is in [`engineering/specs/`](../specs/README.md).

- **Removals (S1):** System Update, the GET maintenance URLs, Razorpay, Paystack and Flutterwave, `EnsureUserIsTeacher`, the duplicate route registration; `ResponseService` returns instead of exiting (162 v1 snapshots unchanged).
- **Apps (S2):** `AppConfig` from build settings, `eschool_session`, `eschool_notifications`, shared code in `eschool_core`, typed routes, phone, tablet and desktop layouts, web builds of both apps, Hosting targets, smoke flows on Android and in Chrome.
- **Admin panel (S3):** navigation by job from `config/navigation.php`, the Material 3 page components, Home checklist and today's numbers, global search, the year switcher with read-only past years, the new-year wizard, `DemoSchoolSeeder`, Playwright on every panel page at 1280 and 1024 px with axe.
- **Operations (S4):** container roles (web, worker, scheduler, artisan), `infra/gcp/provision.sh`, staging and production deploy workflows, structured JSON logs, `/up/worker`, runbooks, gitleaks, Dependabot, k6 smoke, ADRs 0001 to 0005.
- **Kernel (S5):** queued `SendMail` and `SendPush` with `notification_deliveries`, Amazon SES with the SNS feedback webhook, `domain_events` and `DispatchOutbox`, `audit_log`, `Money` and the `*_cents` columns, `settings.school_id`.
- **Security (S6):** one password rule, teacher status, per-app tokens, CORS limited to the panel and portals, learner invitations, uploads per school with sanitised names, report-only CSP, secrets out of `settings` rows.
- **API v2 and test infrastructure (S7):** `/api/v2` with `GET /me/context`, rate limiters, `Idempotency-Key`, `openapi.yaml` and its contract suite, `packages/eschool_api`, the tenancy scaffold and the role matrix.
- **Close (S8):** the changelog, these appendices, the questions file, the security record, a local run of the self-test matrix, the wiki sweep, Cloud Run wiring for the SES and settings secrets, and the `/staff` fix.

### Exit gate (R0)

| Item | Status |
| --- | --- |
| Production backed up, restore drill passed, staging live, worker and scheduler running seven days without an unhandled failed job | Open: needs the staging project and credentials ([environments.md](../runbooks/environments.md), "One-time setup by the product owner") |
| CI green with the new gates on three consecutive merges | Open: counts from the first merges to `main`. Pint over the whole panel and Larastan level 1 are applied by the lead at the close |
| All UX acceptance tests pass; UX scripts pass four of five tasks per profile | Scripted acceptance paths pass locally ([self-test record](../self-tests/phase-0.md)); UX scripts open (staging, new users); "apps in a browser" blocked by defect D1 |
| Security checks all ticked | Open: 9 (restore drill), 6b (secret values and purge) and 7b (Dependabot on `main`) wait for the environment; D1 and D2 found at the close ([security record](../security/phase-0.md)) |
| The Phase 1 decisions are answered or defaulted | Done: all defaulted (Q0.1 to Q0.6), waiting for the product owner's answers |
| Demo on staging | Open: needs staging |

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
- **Kernel:** mark bounced or complained addresses undeliverable (needs a user column); clear `UNREGISTERED` FCM tokens; the old `MailService` failure log still writes the address; idempotency keys for the legacy v1 and panel money writes (Phase 5, with the ledger); give the new kernel tables their `school_id` values (Phase 1).
- **Payments:** pending payments started on a removed gateway are settled by hand.
- **Tests:** the tenancy suite asserts only that each route exists until Phase 1 brings a second school; `DemoSchoolSeeder` needs an exam paper and an assignment so marks and assignments can be self-tested from the seed.

## Decisions register (Appendix F)

Decided by the product owner so far: grade heads correct registers, overturn sanctions and message all grade parents without approval; intake fee, dates and retention are each school's choice within a platform maximum; AI coding agents do all engineering, with no human engineers; every new feature is switchable per school; Razorpay, Paystack and Flutterwave are removed and Stripe kept; WhatsApp alerts are declined.

Defaults taken by engineering until the product owner answers are listed in each phase's questions file.
