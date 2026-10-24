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
