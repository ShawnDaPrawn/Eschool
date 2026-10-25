# Facilitator access verification — 25 October 2026

The facilitator identity now has a dedicated sign-in and restricted Material 3
shell. Teacher bootstrap remains separate. Vendor/unknown/mismatched identities
remain unavailable. This delivers staff access and the actual shared groups and
message screens; it does not claim the remaining community modules are complete.

| Check | Evidence |
| --- | --- |
| Backend/session and all v2 routes | 290 tests / 1,949 assertions passed, including all v2 routes and group regressions |
| Existing sign-in/token/password/role regression | 39 tests / 297 assertions passed |
| Dedicated client/widgets/actual matching native identity entrypoint | Full combined teacher suite: 54 passed; actual matching facilitator entrypoint: 8 passed; same-account refresh in 13 focused tests |
| API client serialization/contracts | Full SDK suite: 61 tests passed, including nullable cookie token, setup policy versions and sign-out payload |
| Browser entrypoint | Actual initializeApp with FLAVOR=facilitator: sign-in, scoped real group list, expected-account header, sign-out passed in headless Chrome |
| Native metadata/source | Trusted bundle-ID Firebase guard, facilitator URL-launcher whitelist, configuration/plist invariants and Crashlytics gate pass |
| Static/source checks | Full Larastan and Pint passed; Flutter analyzer zero errors/warnings with 27 style infos (24 existing, 3 in the new facilitator source); actionlint/whitespace passed |
| API description | Configured strict Redocly lint passed; pinned generation produced 905 files |

Backend tests cover exact-school credentials, ended/wrong roles and parent-only
refusal; no session before password and current-policy completion; stale policy
atomicity; teacher-token/push preservation; cookie transport and web-module direct
API refusal; role/policy revocation; current-token-only logout and account-header
refusal; counsellor without a Teacher record and no unauthorized group menu.

The actual shell tests use the production localization delegate with a small
subset of real catalogue labels to avoid an asset-decoding isolate waiting under
fake widget time. Production/browser smoke uses the full real asset bundle.
Phone and desktop layouts (390/1280) exercise policy/password setup and the scoped
group screen. Refresh invalidates old readers even if identity and flags return
unchanged. The session and private views live in memory; OS termination requires
sign-in and unsaved local drafts are not durable. An offline refresh keeps the
identity hidden until authorization succeeds; offline logout clears local access.

No teacher Firebase/Hive auth/notifications bootstrap is called by the Dart
facilitator entrypoint. Native teacher Firebase initialization can still precede
a contradictory Dart flavour refusal; reserved native iOS IDs never initialize
teacher Firebase. iOS received structural/source validation only: this environment
has no macOS/Xcode/device. Named Android debug compilation runs in dedicated CI;
no local Android build, signing, store release, production deployment or live
provider qualification is claimed. Browser API responses are synthetic and
exercise the real app/repositories rather than a live school deployment.
