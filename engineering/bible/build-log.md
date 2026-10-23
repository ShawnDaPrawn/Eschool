# Appendix E: build log

What each phase delivered, what it deferred and why, and which of our questions are still open. Updated when a phase closes; a row is added when a phase starts.

| Phase | Status | Started | Finished | Delivered | Deferred | Open decisions |
| --- | --- | --- | --- | --- | --- | --- |
| 0 Groundwork | Built and merged; gate items that need the cloud or people open | 2026-10-21 | 2026-10-22 (code) | Queue, scheduler and worker; mail through SES with delivery records; outbox; audit log; money in cents; API v2 with `/me/context` and `openapi.yaml`; web builds of both apps; panel navigation by job, Home checklist, search, year switcher, new-year wizard; security fixes; staging and production definitions, deploy workflows and runbooks; CI gates. [Details](#phase-0-delivered) | Everything that needs the cloud project or people (staging, production changes, the restore drill, SES, seven days of the worker, UX scripts, the demo) and the follow-ups below. [Details](#phase-0-deferred-with-reasons) | Q0.1 to Q0.6 defaulted and waiting for the product owner; Q0.8 onwards defaulted by the streams ([phase-0-questions.md](../phases/phase-0-questions.md)) |
| 1 Schools and identity | In progress ([plan](../phases/phase-1.md)) | 2026-10-22 | | | | Q1.1 to Q1.5 defaulted ([phase-1-questions.md](../phases/phase-1-questions.md)) |

## Phase 0 delivered

Built on the phase branch by streams S1 to S10 between 2026-10-21 and 2026-10-22; the changelog's "Unreleased" entry lists it for users and deployers, and each feature's spec is in [`engineering/specs/`](../specs/README.md).

- **Removals (S1):** System Update, the GET maintenance URLs, Razorpay, Paystack and Flutterwave, `EnsureUserIsTeacher`, the duplicate route registration; `ResponseService` returns instead of exiting (162 v1 snapshots unchanged).
- **Apps (S2):** `AppConfig` from build settings, `eschool_session`, `eschool_notifications`, shared code in `eschool_core`, typed routes, phone, tablet and desktop layouts, web builds of both apps, Hosting targets, smoke flows on Android and in Chrome.
- **Admin panel (S3):** navigation by job from `config/navigation.php`, the Material 3 page components, Home checklist and today's numbers, global search, a year switcher that opens past years read-only, the new-year wizard, `DemoSchoolSeeder`, Playwright with axe over every panel page at 1280 and 1024 px.
- **Operations (S4):** container roles (web, worker, scheduler, artisan), `infra/gcp/provision.sh`, staging and production deploy workflows, structured JSON logs, `/up/worker`, runbooks, gitleaks, Dependabot, k6 smoke, ADRs 0001 to 0005.
- **Kernel (S5):** queued `SendMail` and `SendPush` with `notification_deliveries`, Amazon SES with the SNS feedback webhook, `domain_events` and `DispatchOutbox`, `audit_log`, `Money` and the `*_cents` columns, `settings.school_id`.
- **Security (S6):** one password rule, teacher status, per-app tokens, CORS limited to the panel and portals, learner invitations, uploads per school with sanitised names, report-only CSP, secrets out of `settings` rows.
- **API v2 and test infrastructure (S7):** `/api/v2` with `GET /me/context`, rate limiters, `Idempotency-Key`, `openapi.yaml` and its contract suite, `packages/eschool_api`, the tenancy scaffold and the role matrix.
- **Close (S8):** the changelog, these appendices, the questions file, the security record, a local run of the self-test matrix, the wiki sweep, Cloud Run wiring for the SES and settings secrets, and the `/staff` fix.
- **Gate fixes (S9):** the web portals sign in with the panel's session cookie (sign-in starts a regenerated session, logout ends it, `SESSION_DOMAIN` set by `provision.sh` on the NavigateEd domain, bearer builds elsewhere); `/storage` answers the portal origins with CORS headers; the teacher app's attendance button and lesson cards are labelled; the fees list shows the whole homeroom name; a guard for invalid browser language tags; an exam paper and an assignment in `DemoSchoolSeeder`.
- **Privacy sweep (S10):** the engineering records, wiki, docs and code comments describe our work in our own words; the Build Bible stays private.
- **Lead:** Pint over the whole panel (CI checks it), Larastan level 1 with a baseline for the seed's existing findings, and the fixes from Codex review on this phase's pull request.

### Exit gate

Our own reading of what closing Phase 0 takes (the Build Bible, section 6, has the gate itself).

| What has to be true | Status |
| --- | --- |
| Environments: a production backup and a successful restore drill; staging running, with the worker and scheduler up for a week and no unhandled failed job | Open: needs the staging project and credentials ([environments.md](../runbooks/environments.md), "One-time setup by the product owner") |
| CI: the new gates pass on three merges in a row | Open: counts from the first merges to `main`. Pint over the whole panel and Larastan level 1 are applied and enforced in CI |
| UX: the scripted acceptance paths pass, and new users get through most tasks of the UX scripts on each profile | Scripted acceptance paths pass locally, including the web portals after the D1 and D2 fixes ([self-test record](../self-tests/phase-0.md)); UX scripts open (staging, new users) |
| Security record complete | Open: 9 (restore drill), 6b (secret values and purge) and 7b (Dependabot on `main`) wait for the environment; D1 and D2, found at the close, are fixed ([security record](../security/phase-0.md)) |
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

Follow-ups found in Phase 0, carried into Phase 1's plan (the defects found at the close, D1 to D5, are fixed):

- **Portal hosting:** a Hosting CSP that allows what Flutter needs (`wasm-unsafe-eval`, CanvasKit from `www.gstatic.com`, PDF.js from jsDelivr), or self-hosted copies; native App Links and universal links; browser uploads, payments and web push (Phase 1).
- **Apps:** the learner fees screen still lists Paystack and Flutterwave (disabled, as the server omits them); the `pdfx` plugin is untested on a real device; the `html <0.15.7` pin; the smoke flows find buttons by English text.
- **Panel:** older pages' icon-only actions without an accessible name (listed in `admin-panel/e2e/allowlist.json`), fixed as each page moves to the page pattern; the fee settings screenshot in the user docs (`documentation/static/images/admin/13.png`) still shows the removed gateways' tabs; the unused key `currency_not_supported_by_razorpay`.
- **Kernel:** mark bounced or complained addresses undeliverable (needs a user column); clear `UNREGISTERED` FCM tokens; the old `MailService` failure log still writes the address; idempotency keys for the legacy v1 and panel money writes (with the ledger work in a later phase); give the new kernel tables their `school_id` values (Phase 1).
- **Payments:** pending payments started on a removed gateway are settled by hand.
- **Tests:** the tenancy suite asserts only that each route exists until Phase 1 brings a second school.

Product-owner decisions are in the private Build Bible; the defaults engineering takes until the product owner answers are in each phase's questions file.

## Delivery update — 23 October 2026

The product owner confirmed the Phase 0/1 product defaults and authorised
continued development irrespective of pending phase checkpoints. Implementation,
automated verification, staging verification, acceptance and release are tracked
separately in [the delivery register](../phases/outstanding-work.md). Additional
staging/email spending is capped at US$10/month. Staging access and first-time UX
participants are unavailable; this does not block independent implementation.
The previously listed Dependabot activation is evidenced by the dependency PRs
already merged. The old tenancy-scaffold-only note is superseded by the merged
two-school route suite. Production acceptance remains separate.

Guardian approval and invitation work merged as PR #67 after all CI checks
passed. The approval enforcement rollout remains disabled pending family
transition verification. Dated enrolments and lifelong student numbers are
implemented on their next feature branch, including a resumable backfill,
legacy projections and school-safe cleanup; staging migration rehearsal and
production acceptance remain outstanding.

PR #68 merged dated enrolments and student numbers after all 24 checks passed;
PR #69 merged school module controls after all 24 checks passed, including the
browser settings flow and Android. Handles, their guardian review and shared
app settings are implemented on the next feature branch. Local backend,
identity/deletion, package and browser checks pass; feature PR CI precedes
merge. These merges do not claim staging acceptance or enable production
approval enforcement.

PR #70 merged global handles and guardian review after all 25 checks passed.
Notification rules and preferences are implemented on their next feature
branch: per-portal school settings, shared app choices, durable recipient
fanout, encrypted jobs, delivery-time access checks and guardian handle
requests. Local and CI verification is recorded with that feature; real mail,
push, phone and owner acceptance remain separate staging work.

PR #71 merged notification settings and durable delivery after all 25 checks
passed. A Phase 0 restore procedure review corrects quarantine ordering, worker
stop semantics, queued intent cleanup and cost boundaries. Shell examples parse
and the sanitisation SQL passed nine assertions on a separate local MariaDB
transaction, preserving people and academic rows. No cloud restore or acceptance
is claimed; credentials and a feasible capped staging plan are still required.

PRs #72–#74 merged invitation mailbox binding, the corrected restore quarantine
procedure and legacy parent child-response ownership after all 25 checks passed
on each PR. The configurable profile schema/API slice adds scoped defaults,
encrypted custom values, field policies and typed forms/configuration. Local
contract and security tests pass; the panel/app/export flow and legacy storage
bridge remain in progress. Staging acceptance remains separate.

PR #75 merged the protected profile schema/API after all 25 checks passed,
including 897 backend tests. The editor/form slice adds the M3 field editor,
shared app profile form, metadata-only context and permitted export. Local
phone/desktop tests cover partial edits, failed-save retention, cross-school
subject selection and clearing stale private values. Browser, full regression
and exact-head CI evidence is recorded with this feature. Legacy storage
backfill, compatibility writes, secure files and caste/religion retirement
remain implementation work; no staging or production acceptance is claimed.

The My school backend/editor connects the existing shared app screen to scoped
contacts, branding, published policies and current term dates. Generated DTOs
replace handwritten document/date decoding, while older partial answers remain
supported. Permission, school isolation and publishing tests accompany the
feature. Published school PDFs are explicitly public; private person files
remain part of the profile migration work. Staging acceptance remains separate.

PR #76 merged the configurable profile editor/forms, metadata context and
permitted export after all 26 checks passed. The final backend job passed
906 tests with 11,667 assertions; both Android suites, browser/accessibility
and Chrome flows passed. Profile audits explicitly record the selected school.
Legacy migration/files and broader Phase 1 implementation remain outstanding.
