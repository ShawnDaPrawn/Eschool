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

Private profile-file fields extend the same form metadata with encrypted
PDF/PNG/JPEG storage, per-read person/field authority, stale-link revocation and
authenticated in-memory app previews. Panel uploads preserve other unsaved
profile values. Focused contract/access/rollback tests, shared app tests and the
panel browser flow accompany the change. Legacy public-file migration,
retention and external acceptance remain separate work; final regression and
PR checks are recorded in the feature self-test.

PR #77 merged My school contacts, branding, publications and term dates after
all 27 checks passed, including 924 backend tests and both Android suites.
Private files are tracked in #78; legacy migration, pictures and the remaining
Phase 1 closeout items remain separate implementation work.

PR #78 merged encrypted private profile files after all 27 checks passed,
including 940 backend tests and native image/PDF previews. A separate bounded
legacy backfill adds school/profile-specific dry runs, deterministic field
mapping, shared value validation and insert-only encrypted values. Imported
fields start hidden for review; existing edits and explicit clears win. The
command reports malformed/ambiguous rows and pending files without private
contents. Compatibility writes, archived records, public-file migration and
legacy caste/religion storage retirement remain implementation work. No live
migration, staging acceptance or production release is claimed.

The bounded backfill gains explicit --files for local legacy PDF/PNG/JPEG
private copies, preserving source objects and v1 paths. Path/folder/school,
symlink, size and MIME checks precede bounded reads; names and contents use
the existing encrypted private-file service. Hidden field review, protected
reads, retries and outer-transaction cleanup have dedicated synthetic tests.
Public-file retirement and compatibility writes remain separate work. No
live data migration or external acceptance is claimed.

## 23 October 2026: start Phase 2 with academic references

Begin independent Phase 2 work while Phase 0/1 remaining implementation and
external acceptance stay open. The first bounded slice provides global read-only
R–12 grade/phase and seven-point basis-point references, plus an explicit audited
school-scoped mapping of existing classes. Existing class names, legacy grades,
marks, semester contracts and result calculations are preserved; unmapped classes
remain valid. No assessment ratio, promotion or pilot result is invented.

The phase plan, pending decisions, feature spec and self-test record distinguish
automated verification from staging, full-term pilot acceptance and production
release. Terms, subject standards, assessment/mark engine, teaching groups,
timetable, attendance, reports, content, messages and pilot remain future slices.
No staging resources, live data migration or production release are performed.

## 23 October 2026: school terms and semester retirement

The owner confirmed no installed clients use semesters and authorized retirement
rather than a compatibility bridge. A coordinated in-place rename preserves IDs,
academic foreign-key links and permission grants; bundled consumers use terms.
Grade-aware calendars, optional lock/report dates, school templates, a Material 3
management page and new-year template seeding share validated scoped services.
Template preview does not mutate calendars; apply adds missing rows atomically,
retains existing dates and rejects overlap or invalid leap-day conversion.
Staging, independent assessment/pilot acceptance and production release remain
separate; no live migration or cloud resources are part of this implementation.

On 24 October 2026 the product owner confirmed there are no legacy clients
and removed legacy-storage retirement from Phase 1 closeout. Compatibility
writes for those clients and archived legacy profile migration are likewise
outside that closeout path. Existing data and public objects remain in place;
privacy retention and access controls still apply. Phase 0 staging evidence
and the other Phase 1 implementation and acceptance work remain open.

The managed cloud environment reported no outbound identity, secret or runtime
variable bindings on 24 October 2026. Staging resources and spend were not
inventoried or changed; real mail, restore and worker observation remain pending.

## 24 October 2026: continue Phase 2 academic implementation

The owner requested autonomous completion of remaining Phase 2 work, referring
back to the private Build Bible. Subject catalogue/mapping adds reviewed operator
imports, immutable sourced revisions, explicit publication and school-scoped
mapping/creation with a Material 3 page. The historical DBE-NCS-2015 baseline has
629 grade-specific codes and no guessed assessment weights. Existing marks and
subject payloads remain intact. Later academic slices continue while genuine
staging/pilot/independent-review evidence stays open.

## 24 October 2026: versioned curriculum foundations

Added bounded, audited curriculum/topic/task/question definitions, independent
term publication, exact mark units, optimistic revisions and same-year provenance.
Separate term/annual components accommodate subject-specific policy without
inventing ratios. Material 3 editor and explicit new-year draft carry use the same
service as v2 APIs. Source confirmation, marks engine, remaining academic screens
and external pilot evidence continue as separate work.

## 24 October 2026: dated teaching structures

Implemented school-scoped teaching groups, effective memberships and teacher /
homeroom / leadership allocations, explicit class grade mapping forms, audited
revision checks and interval validation. Stable existing-app projections have
fresh date checks and preserve historical IDs. Allocated teachers can plan future
curricula; actual capture needs a current grant. No marks are created or copied
by assignment. Software verification continues separately from the genuine
staging, source-policy, pilot-term and independent review evidence.


## 24 October 2026: exact marks and release evidence

Implemented rational task/component calculations with final-only rounding,
question capture, optimistic corrections, scoped moderation, school-timezone
locks, queued immutable snapshots and selected-learner release previews/pins.
Compatible same-year definitions retain marks; publication cannot discard
recorded tasks. Incomplete future annual definitions do not block ready terms.
Existing exam writers share exact recording/lock/audit rules, and official
releases project protected existing-app results without inventing promotion
thresholds. Added generated v2 client models and a bounded weekly CSV comparison.
The adaptive capture client is the next feature. Synthetic tests are software
evidence only; real pilot/staging/security/UX/production acceptance stays open.


### Timetable engine and import contracts — 24 October 2026

Added school rooms/bell schedules, dated lessons, actual-time clashes across
periods and school-year ranges, shared-learner conflicts, preview fingerprints,
atomic import mappings and revision-checked cancellation. Fresh teacher/family
week and next-period reads use dated rights. Protected v1 projections retain
field types and stable IDs; additive room labels update the five affected API
snapshots. The panel/shared app editor and home card follow as a client slice.
Real vendor exports, staging, device timing and pilot acceptance remain separate.
### Adaptive academic mark clients — 24 October 2026

Added the shared academics package, keyboard/Excel/question capture, server
running previews, conflict comparison, explicit moderation/release preview and
family released-result views. Both apps retain their existing phone exam flows;
new entry points require the server capability. Context changes clear private
academic screens. Package, backend and real-app Chrome fixtures verify the
flows; classroom UX, staging, real full-term evidence and production approval
remain separate. See the mark-client runbook and Phase2 self-test record.


### Phase 2 timetable clients (24 October 2026)

Backend#91 is merged after31 green checks. The shared adaptive week/editor and
room/teacher home card are wired into both apps. The panel configures rooms and
bells, previews placements and explicitly maps CSV/TSV exports. Revisions, stale
responses, context changes, school timezone and overlapping calendars are covered.
Teacher/parent Chrome and1024/1280 panel flows pass; package35, SDK32, teacher28,
family45 tests pass. Local combined PHP ran1,240 tests;1,238 passed and two
presentation checks were fixed and verified with35 targeted checks. Client
exact-head CI and real staging/classroom/pilot acceptance remain separate.

### Phase 2 academic attendance backend (24 October 2026)

Dated homeroom/period registers, durable UUID/hash/revision sync, grade-head
corrections, QR issue/rotation, protected legacy projections and school cutoff
notices are implemented. Queued transport rechecks guardian rights. Contract,
tenancy/migration, old API and notification checks are recorded in the Phase2
self-test file. SDK generated from the additive contract. Shared register clients
and panel overview remain a separate slice; actual device timing, staging
cutoff delivery and full-term school evidence remain pending.

Attendance clients/workbench: shared adaptive teacher register with account-owned
encrypted offline operations, ordered retries and explicit conflict review; own-child
Home attendance/QR, panel counts/corrections/cutoff, and bounded atomic form capture.
Local shared/app tests and teacher/parent Chrome flows pass; panel capture/conflict
and read-only flows pass at both desktop widths with accessibility/overflow checks.
Staging, real classroom/device timings and full-term pilot evidence remain open.

### Phase 2 report service implementation (24 October 2026)

School report designs and comment banks, selected learner previews, exact snapshot
pinning, grade-head/principal signatures, queued encrypted private PDFs, explicit
release, own-child requester-bound expiring downloads and read/notifier records
are implemented in a dedicated branch. Independent draft comments use per-scope
CAS; changes invalidate signatures, stale jobs cannot reuse an earlier approval
cycle, and published evidence remains immutable. Current leadership can handle
historical reports after a year rollover; administrative comment corrections
require an encrypted audit reason. Backend/API/SDK verification is in progress.
Panel/shared report screens, external delivery and pilot acceptance remain open.

24 October 2026: shared report screens and the native M3 panel workbench add
design/bank editing, explicit learner previews, allocated comments with conflict
review, ordered approval, queued PDF review/release and paged own-child history.
Private PDFs refresh authority on open/resume/export; context changes discard
route memory. Automated/browser verification is tracked in phase-2 self-tests.
No production or full-term pilot acceptance is inferred from synthetic checks.


### 24 October 2026 — mid-year learner changes

Implemented dated join/leave/subject-change services and office workflows, grade
policy defaults/overrides, operation UUID/fingerprint review, retained cancellation
history, request-time learner access and private queued signed exit packages.
No billing/activation is inferred. The owner-authorized permissive checkpoint
approach allows joins before subject allocations and leaves from unmapped legacy
homerooms. Added explicit copy retention without inventing an educational-record
deletion schedule. Verification and merge evidence are recorded in Phase 2 self-tests;
production and external pilot acceptance are separate.


### 24 October 2026 — private content service and contracts

Implemented encrypted quarantine sources, fail-closed ClamAV scanning, a private
Python/MarkItDown service with bounded offline audio/video transcription, queued
conversion/recovery, explicit draft review/publication, immutable versions and
dated group-only family reads. Added school transcription controls/duration
reservations, notification delivery guards, additive v2 contracts and generated
Dart types. The broader Phase 3 AI kernel is not represented as finished.
Native panel workflows are included; shared app workflows follow in a separate slice; no content capability is yet
advertised in app context. Local verification is recorded in Phase 2 self-tests;
container/real-daemon CI and external acceptance remain separate evidence.


### 24 October 2026 — shared content clients

Added teacher upload/edit/status/retry/review/publication/history/withdrawal and
family library/Markdown readers to the shared academic package and both shells.
The app capability is advertised with this slice. Requests pin school/account;
notification links validate learner/version and resolve only an authorized
child. Uncertain writes preserve UUID/body, conflicts retain local draft text,
and context changes close private routes. Inactive/resumed screens hide and
refresh private content. The initial reader uses route memory; encrypted leased
offline copies follow as a separate implementation slice before Phase 2 wrap-up.
Automated and browser evidence is recorded in phase-2 self-tests; production,
physical-device, human translation and full-term pilot evidence remain separate.


### 24 October 2026 — content merges and encrypted offline reading

The private converter/service/panel (#98) and shared teacher/family clients (#99)
are merged to main after green final CI and resolved review findings. A separate
offline slice implements explicit encrypted copies, fresh bounded batch access
checks, at-most-one-day server/request deadlines, visible expiry, clock rollback
and corruption refusal, 20-copy physical bounds and environment/school/account
key erasure with late-write generation checks. Global session listeners handle
scope changes even after a library closes. Only a transport failure can select
an unexpired copy; HTTP, certificate and cancellation failures do not qualify.
Automated evidence is in Phase 2 self-tests; no pilot or production acceptance
is inferred. Remaining implementation is communication, exports/languages and
pilot/verification tooling.


### 24 October 2026 — communication service and panel

Implemented school/grade/homeroom/group/guardian and saved custom audiences,
role author/approval rules with the grade-head exemption, revision/UUID drafts,
separate review, sanitized previews, school-local schedules, immutable recipient
publication, generic Notifier delivery, selected channels, durable 100-recipient
batches, recovery, transport revocation, own-reader/read state and a native
two-pane panel. The additive contract and pinned SDK are generated together.
Shared app navigation/screens, exports/languages and pilot tooling remain active
implementation work. Automated evidence belongs in Phase 2 self-tests; no
staging, full-term pilot, human translation or independent review is inferred.


### 24 October 2026 - shared communication clients

Both existing apps now expose guarded School messages with adaptive phone and
two-pane layouts. Shared staff composition, current-scope audiences, saved
preview/submission, separate-author approval and withdrawal use the additive
contract. Family readers acknowledge only their own authorized messages. UUID
retries, revision comparison, private lifecycle refresh and authority changes
are covered by automated and actual Chrome flows. Saved list and role-rule
management remains in the panel. Exports/languages and pilot tooling remain
active implementation; external acceptance evidence is tracked separately.


### 24 October 2026 - versioned academic export bridge

Added private panel exports for dated learners, daily homeroom attendance and
raw task scores through explicit school/year/version-bound layouts. Exact
external identities and status codes are supplied by the operator; no universal
SA-SAMS layout is invented. CSV and hash-bound blank XLSX rendering include a
signed provenance manifest; unknown or incomplete source data fails visibly.
Automated and both-width browser evidence is recorded separately from target
SA-SAMS import qualification. Languages and pilot tooling remain active work.


### 24 October 2026 - Afrikaans and isiZulu development drafts

Both apps and the panel now include complete-key af/zu language files, registered
without overwriting existing panel language metadata. Shared academic labels and
mark vocabulary read the current app locale. Automated coverage enforces the
locale files, placeholders, markup and working school glossary; actual asset
widgets and both-width native panel flows pass. These are development drafts,
with fluent and school-champion review explicitly pending, not certified
translations. Pilot tooling and external acceptance evidence remain active work.


### 24 October 2026 - owner cancels pilot stage

The owner explicitly removed the pilot stage and requested Phase 2 completion
today. Pilot import/onboarding, champion feedback/status tooling and the full-term
parallel run are removed from the completion scope. Retained academic, messaging,
export, language and desktop implementation proceeds to final checks and main.
The existing reconciliation command is retained. External release evidence is
still reported honestly; the decision does not authorize production deployment.

### 24 October 2026 - concurrent Phase 3 and Phase 4 kickoff

The owner authorized two isolated agent streams from main `9fc30e42`: Phase 3
AI learning/pastoral care and Phase 4 facilitator/community. The first slices
are the inference service/CLI gateway foundation and native app build identities
with startup isolation. Neither phase is claimed complete. Shared contracts and
feature dependencies are recorded in `engineering/phases/phase-3-4-coordination.md`;
the lead reviews and integrates feature PRs. Existing transcription and teacher
builds remain supported. External provider activation, production/store release
and unrun acceptance evidence are not inferred from this kickoff.

### 25 October 2026 - relief cover API and dated integration

The backend cover increment now has scoped v2 APIs, a combined generated client,
dated effective-teacher timetable projection, reserved cover clash checks and
generic notices with recipient/source/revision checks at transport. Focused
API/domain tests passed22/171, full configured static analysis and strict schema
lint passed, and two SDK date/nullability regressions passed. Panel/shared-app
workbenches follow; support plans, leadership and transfers remain in progress.
No external classroom/device/staging acceptance or production activation claimed.

### 25 October 2026 - private relief-cover panel

The school panel now handles named cover requests, available substitute
assignment, explicit source review/cancellation and dated personal lessons.
Private draft errors and early read-only-year refusals return escaped input
without session flash; the shared private-form registry also covers group
materials. Actual browser workflows passed at1024/1280 with accessibility,
overflow, unique-ID and own-origin error checks. Panel access and mutation
regressions, catalogue checks, Pint and configured static analysis pass.
Shared app cover screens and expected-account binding follow separately; no
external classroom/staging/device acceptance or whole-phase completion claimed.

## 2026-10-25 — houses, append-only points and permanent awards domain

Added school-owned houses, dated memberships, categories, configurable daily staff
issue limits and reasoned append-only reversals, plus scoped reusable award
categories/definitions, year windows, versioned nominations, retained presentations
and outside awards. Permanent profiles query award presentations only. Exact UUID
receipts retain references and HMAC intent, with current authority before replay.
The grade-head nomination adapter is now available for My grade integration.
Twenty-two MySQL tests (97 assertions), Pint and whole-app static analysis pass;
API/panel/app/live delivery and external acceptance follow separately. No production
activation or whole-phase completion is claimed.

## 2026-10-25 — recognition API and generated client

Added 19 scoped v2 recognition operations and 41 schemas with shared service/request
validation, aggregate counters, private year history, separate permanent profiles
and nomination/presentation actions. The integrated API/contract cohort passed391
checks (1,966 assertions); final recognition30/273 and configured static analysis
pass. The pinned generator produced1,410 Dart files; existing83 plus new4 SDK tests
and strict lint pass. Preserved parallel Risk/calendar additions. Panel/assembly,
apps and live/notice integration remain separate increments; no phase or production
completion is claimed by these checks.

## 2026-10-25 — houses, awards and assembly panel

Added the school panel workbench for dated house allocations, configurable point
categories/caps, reasoned reversals, scoped reusable award definitions, nomination
windows and category appointments, reviewed/approved assembly presentations and
outside awards. Permanent profiles contain presentations only. Assembly excludes
private nomination/review reasons; private draft failures never enter session flash.
37 MySQL checks/321 assertions, full Pint, configured static analysis and TypeScript
pass; actual Chromium award/reversal/assembly/certificate-correction workflows pass
at390/820/1280 with accessibility, overflow, duplicate-ID and error checks. Native
clients, guarded notices and live latency remain independent integration work.
