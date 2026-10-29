# Consolidation security and integration evidence — 29 October 2026

This supplements the original Phase 0 model with the current Phases 3–5 surfaces.
It is an implementation/evidence inventory, not a penetration-test certificate.
Execution evidence belongs to the exact-head PR checks and feature self-tests.

| Boundary / threat | Current safeguard and regression family | Remaining external evidence |
| --- | --- | --- |
| Replaced browser/bearer session reads old private state or a delayed401 signs out its replacement | Shared typed session identity, request capture, context generations and serialized cache erasure; official AuthCubits clear selections on authenticated-account replacement. `packages/eschool_session/test`, both apps' sign-out/session-watcher tests; session-replacement spec and self-test. Browser marker is non-authorizing. | Investigate persistent startup ownership when another portal changes a shared cookie while this app is closed. Do not claim this unvalidated variant is repaired. |
| Native bearer/profile data extracted from plain local files or device backup | Whole authBox uses AES with a random OS-keystore key; migration verifies the encrypted copy before removing old support/Documents files. Keychain key is device-bound; missing restored keys require sign-in. Android backup remains disabled. Seven disk/fault/migration tests and shared-session tests. | Physical iOS/Android restore, locked-device and hardware-keystore acceptance. No claim of protection from a fully compromised running device. |
| Vendor identities/readers abuse staff or another school | Distinct Vendor identity/realm, fresh scoped operator and Finance policies; reader navigation and FormRequest share the same authority predicate. Vendor operator/catalogue/order/settlement suites, Cards suites and wallet-payment browser flows. | Physical payment readers/NFC/camera and school operator acceptance. No external gateway is configured. |
| Financial retries, drift, mixed currencies or competing effects | Integer cents, composite-school joins, permanent operation outcomes, sealed history, school serialization and raw-posting verification. Money and Platform concurrency suites; read-only module history is preserved. Bounded Finance projection checks cached accounts against raw postings and verifies authority before/after the read. | Larger-history and contended deployment observations; retain reconciliation and backups. |
| Portrait/SUN/Health, wellbeing or anonymous disclosures become broadly visible | Purpose/role/school/owner policies, consent and dedicated private response routes; private views erase on background/revocation. Wellbeing, Cards, People and pastoral security suites. Public reporting remains a distinct anonymous boundary. | Independent safeguarding/privacy review, school policy and physical persona journeys. |
| AI/converter workers leak private material or incur uncontrolled use | Existing gateway metering, moderation/quarantine, purpose-scoped input and worker/source boundaries; Ai feature, quarantine, inference and worker suites. Provider activation remains separately configured. | Live-provider/model/cost/quality acceptance and external security testing. |
| Live channels retain revoked authority | School/account/channel bindings, entitlement refresh and private view generation boundaries; Live and feature-specific revocation suites. | Deployed Reverb/network observations and real-device reconnect behavior. |
| Script injection on canonical panel workbenches | Enforced CSP for wallet-finance and vendor-catalogue routes; fresh request nonces appear only in trusted Blade templates. No automatic nonce rewriting of response HTML. Other legacy pages retain report-only CSP. Backend policy tests plus real-browser trusted-script/injected-script controls. | Expand enforcement only after each additional surface is qualified; fluent/user acceptance remains separate. |

## Corrections to the original model

`ResponseService::logCurlRequest` no longer logs request field values: it records
method/path and bounded field names/ids. Official invitation requests also suppress
client request logging. Language-controller exceptions now use `report` rather
than stringifying them around the central exception handler. Remaining legacy
call sites require their own review; this is not a repository-wide proof of no
sensitive logging.

Production is an existing configured deployment, rather than an unrun definition:
release v0.1.0's protected deploy/backup/smoke workflow completed in
[run 40791836522](https://github.com/ShawnDaPrawn/Eschool/actions/runs/40791836522).
The consolidation release still requires its own qualified main, new release tag,
backup and live verification. No new infrastructure, paid integration or stores
are authorized merely by passing software tests.

## Legacy capability coverage

| Legacy capability | Consolidation decision |
| --- | --- |
| Seed fee collectors, class-fee copying, checkout and old money reports | Replaced by canonical plans, family accounts, bills, receipts, allocations and statements. Active writers/readers/UI removed. Old v1 fee routes are authenticated private retirement responses, not ID adapters. |
| Old physical fee schema / cents-migration metadata | Retained inertly for additive rollout and rollback. No destructive migration in the release that first stops using it. |
| Marks and academic report projections | Retained: they still serve installed clients and academic workflows. |
| Lessons, chapters/topics and attached learning material | Retained: complete content/data parity has not been established. |
| Public notices and ordinary one-to-one chat | Retained: private communications and group conversations have different audiences and behavior. |
| Timed exams | Retained: practice and Exam Coach do not replace timed assessment. |

New immutable ledger/domain history is never discarded as legacy. No Phase 6 work
or gateway adapter is included. External acceptance does not freeze independent
safe development, and an unrun check must never be described as passed.
