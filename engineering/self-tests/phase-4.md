# Phase 4 verification

## Native build-identity foundation, 24 October 2026

Scope: first part of step 1 only. Separate native identities, scheme/configuration
references and bootstrap refusal are implemented. No facilitator role, group API,
menu, case access, vendor workflow or usable facilitator product is claimed.

| Check | Actual evidence |
| --- | --- |
| Teacher legacy/default identity dispatch and mismatch/refusal | 6 focused Flutter tests passed |
| Explicit facilitator identity, including actual initializeApp boundary | 7 tests passed with --flavor facilitator and matching FLAVOR |
| Explicit vendor identity, including actual initializeApp boundary | 7 tests passed with --flavor vendor and matching FLAVOR |
| Native teacher with contradictory vendor FLAVOR | 7 tests passed; actual entrypoint stays unavailable |
| Full existing teacher app suite | 39 tests passed |
| Full teacher analyzer | Zero errors/warnings; 23 existing style infos |
| Unavailable page, both reserved identities | Widget checks at 390/1280; no authenticated controls, overflow or widget exceptions |
| Native metadata integrity | OpenStep parser: 75 objects, 18 named configs, 12 unique entries in each project/target list, 3 shared schemes, 9 Pod mappings |
| Workflow syntax and whitespace | actionlint and git diff --check pass |
| Named Android APK compilation | Dedicated staff-flavors CI builds facilitator/vendor debug APKs; pending CI, not run locally |
| iOS compile/sign/device/store | Not run: no macOS/Xcode/signing or app registrations |

The unavailable path dispatches before teacher Firebase, auth/storage, notification,
repository, routing or deep-link setup. Build flavor is never server authorization;
future roles/owned groups still require per-school policies and endpoint tests.
Teacher no--flavor commands and native IDs are retained. Named teacher variants
use the same existing configuration. Reserved IDs are development foundations;
the existing teacher iOS Firebase plist is excluded for facilitator/vendor configs.
No live account, card hardware, provider service or store listing was provisioned.

The first metadata audit caught references inserted into the wrong Xcode list.
Anchored list replacement fixed the issue; the committed parser checks both list
ownership and uniqueness, not just the presence of names. Metadata checks do not
substitute for compiling in Xcode. The dedicated Android workflow compiles the
conditional Gradle branches that the default teacher CI would not exercise.

Physical-device, browser integration, staging, user acceptance and production
release are separate evidence; none is inferred from widget/source tests.

### Native Firebase startup review follow-up

Review found that iOS AppDelegate configured Firebase before Dart even when a
reserved build excluded GoogleService-Info.plist. The native boundary now uses
the signed bundle identifier, independently of Dart FLAVOR, to gate Firebase,
notification delegation and generated plugin registration. Teacher behavior is
retained; reserved/unknown native identities still start the Flutter engine but
never initialize teacher services. SceneDelegate has no additional startup hook;
Android MainActivity contains no explicit Firebase initialization and reserved
Android variants do not apply the Google Services plugin.

The native audit passes for all 12 target configurations: teacher identity and
plist availability, reserved identities and exclusions, the Info.plist identifier
binding, the native allowlist and guarded launch/plugin call sites. This is
source/configuration evidence only. Actual iOS compilation and native device
launch remain unrun because this environment has no macOS/Xcode.

### Android resource generation follow-up

The first native CI build rejected the new generated app-name resources because
the current Android Gradle plugin disables custom resource values by default.
The application now explicitly enables `buildFeatures.resValues` for the legacy
teacher and named staff variants. This addresses the identical configuration
failure in both reserved APK builds and the existing teacher smoke run. Local
native metadata validation passes; actual Android compile/smoke evidence remains
the final-head CI result, not a source-check claim.

The native Crashlytics upload build phase also skips reserved/unknown native
bundle identifiers before any upload command. The audit syntax-checks the full
script and executes only its isolated leading gate for all 12 configurations,
plus empty/unknown identities (14 cases); it never executes the upload command.
Native teacher identity preserves its existing Firebase startup. A contradictory
Dart FLAVOR is refused later by the Dart bootstrap; it does not prevent the
trusted teacher native build from initializing Firebase before Dart starts.

Five isolated negative audit fixtures were also rejected: unconditional Firebase
startup, admitting the facilitator native ID, removing the native plugin guard,
hardcoding the teacher plist identity and admitting a reserved symbol upload.
The production source was unchanged by these temporary mutation checks.


## Activity groups backend and communication integration

Implemented the school-scoped seasonal hierarchy, eligibility, dated staff/member
allocations with cancellation, session registers, encrypted authored Markdown and
UUID/hash receipts. Activity audiences cascade only through authorized groups.
The module starts disabled. The group-management panel/shared groups screens and
usable facilitator sign-in shell are separate follow-on work; none is claimed here.

Evidence: all 14 activity operations have successful response-contract coverage
and per-route guest/wrong-current-role/foreign-school refusal checks. Tests cover
owner/group boundaries, revoked roles/guardians, scoped descendant grants,
transaction rollback, optimistic conflicts, role/module revocation before replay,
cancellation of current/future grants and retained attendance. A parent who is
also staff never gains peer roster/draft/contact access from parent visibility.

Legacy v2 message editors are protected by explicit activity_groups=1 negotiation.
Default scopes/draft lists exclude unsupported activity kinds; direct legacy
editing gets a clear upgrade response. Published readers keep their old payload.
The existing panel explicitly opts in and its actual HTTP form saves activity
scopes. The shared app editor remains opted out until the UI follow-on tests it.

Pinned SDK generation uses date-only strings and preserves false cancellation
flags; full group PUT clears omitted nullable eligibility fields consistently.
Group read models include school_id for client-side identity binding.

No new group-management browser screen, native facilitator app, binary material
upload, provider/device/staging/performance or production qualification is claimed
from this backend slice. Final verification counts and CI are recorded on its PR.

Local backend/communication/contracts/tenancy/authorization/language cohort:
667 passed, 28,312 assertions. After final future-session and read-scope identity
checks, the focused activity/migration cohort passes 23 tests, 423 assertions.
Full SDK suite: 53 passed, including four activity-specific wire tests; generator
7.25.0 reproduces 811 Dart files. SDK analysis has zero errors/warnings (296
generated style infos). Configured strict OpenAPI lint, changed PHP Pint and
full Larastan pass. Final complete CI/review remains required before merge.

Activity-list revocation follow-up: cancelled learner memberships are excluded from list metadata as well as detail, audiences and attendance. The existing cancellation regression was first observed failing without the filter, then passed after the fix.

## Group management panel

Implemented manager-only group definitions/tree/eligibility/seasons, named dated
learner and staff allocations, cancellation, sessions, explicit attendance and
sanitized Markdown materials in the existing Material 3 panel. Failed form data
stays in its private response without plain session flash; conflict review uses
the current revision and a new operation. Shared apps remain the next increment.

Initial domain/panel integration: 24 tests / 450 assertions passed. Additional
malformed-input, navigation, read-only-year, localization and browser checks are
recorded when run below; no unrun browser/device evidence is inferred here.

Panel evidence: 34 backend/panel/navigation/bootstrap tests with 1,007 assertions,
plus the explicit historical-year write regression (1 test / 5 assertions).
Malformed form arrays return 422 without a rendering exception or plain old-input
flash. Language catalogue checks: 4 tests / 22,237 assertions. Full Larastan: no
errors. TypeScript: no errors. Pint and whitespace checks passed.

Actual Playwright group workflow passed at 1024px and 1280px: create group,
choose a named school learner, allocate, schedule, record explicit late status,
create a private Markdown material and refuse the foreign-school group URL.
Both widths passed serious/critical axe, unique-ID, horizontal overflow and
own-origin console/network checks. The browser run first exposed indistinct
inline session links; underlines fixed the accessibility failure. Missing demo
avatar storage linking and canonical demo enrolments were fixture setup issues
fixed without weakening assertions. Browser tests use synthetic data only.


Hierarchy review follow-up: reparenting validates the full retained subtree
height, including archived descendants, against the proposed ancestor depth.
A regression first produced HTTP 200 for a depth-13 move, then returned 422 with
no partial mutation/receipt. The test also refuses an archived descendant at
depth 13 and permits the exact depth-12 boundary. The cancellation regression
now exercises list and detail HTTP routes directly. Full activity suite: 22 tests /
433 assertions; final reparent/cancellation focus: 2 tests / 37 assertions. Full
Larastan passed. Existing panel and Flutter branches receive this shared fix.

Private-write header follow-up: activity API responses explicitly set private,
no-store on every method; Laravel's GET-oriented cache middleware alone does not
cover POST/PUT. The complete 14-operation contract case asserts those headers
on reads and writes: 1 test / 89 assertions passed.

Panel private-response follow-up: successful form redirects and failed-validation
responses explicitly forbid caching. API/panel header and form checks passed:
5 tests / 134 assertions. The shared browser-flow configuration retains the
serialized seeded-write project from #109 while adding activity coverage.


## Shared activity app views

Implemented shared group lists/detail, coach session/material editing and explicit
attendance, with entries/routes in both existing teacher and family apps. The
family view never loads a peer roster. Activity audience compose now opts in only
after widget tests proved exact kind/group/guardian round trips.

Actual verification: complete shared academic feature suite 196 tests passed,
including activity views at 390/1024/1280, no implicit attendance, immutable retry,
conflict review, calendar-date planning, pending-resume privacy, role revocation,
offline draft retention and stale-account response refusal. Complete teacher app
40 tests passed; complete learner/parent app 51 tests passed. Both route suites
refuse a disabled groups deep route before private state loads. Both app analyses
have zero errors/warnings (existing style infos 24 teacher / 26 family); shared
analysis retains only the recorded existing message-file style infos.

No new native iOS compilation, physical device, store listing, live delivery timing
or production/staging qualification is inferred from unit/widget tests. The
facilitator identity still deliberately stops before teacher startup until its
separate scoped sign-in and restricted shell are implemented. Authored group
Markdown is available; private binary materials remain separate follow-up work.

### Panel session/year review correction, 25 October 2026

The regression first returned a redirect after an unauthorized historical
attendance correction through a forged current-group URL. Session/group binding
now returns 404; the matching historical-group URL returns 423. The existing
attendance, session revision and operation-receipt count remain unchanged.
Focused real-MySQL panel check: 1 test / 5 assertions passed.


### Large operation receipt review correction, 25 October 2026

A real MySQL regression with 40 learners and a 2,000-character attendance reason
reproduced silent truncation to exactly 65,535 bytes under the configured SQL mode.
Initial and forward migrations now use LONGTEXT. The regression passes after
simulating an existing TEXT installation and applying the upgrade: complete
ciphertext, exact decrypted retry, 40 rows, one receipt, one session revision
advance, and refusal to narrow oversized retained history (1 test / 7 assertions).


### Card software completion checkpoint, 26 October 2026

The bounded offline reader and restricted Foundation entry have their own
[offline evidence](card-offline-reader.md) and [classroom evidence](foundation-classroom.md).
The final [NTAG encoding delivery](card-encoding.md) adds explicit private
preparation, isolated encoder transport, a concrete local PCSC/EV2 utility,
immutable recovery and SUN-confirmed status. Its native, SDK, vector/emulator
and real three-width workbench evidence is recorded there. No physical chip,
driver or deployment result is inferred. Earlier sections retain their original
checkpoint state; they do not describe the later scoped app as unfinished.
