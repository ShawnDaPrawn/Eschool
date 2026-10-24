# Staff native build identities

Phase 4 step 1a adds native build identities to the existing teacher shell.
It does not implement the facilitator role, activity groups, vendor ordering,
menus, cases, permissions or APIs. Those arrive as independently tested features.

## Acceptance

- With no flavour selected, the established teacher build and bootstrap work.
- Explicit teacher matches its native ID and uses existing configuration.
- Facilitator and vendor have distinct Android IDs, labels and iOS schemes/configs.
- An unknown or contradictory FLAVOR/native identity never enters teacher startup.
- A reserved facilitator/vendor identity displays an unavailable page before any
  Firebase, auth/session store, notification, API or deep-link initialization.
  Even a cached teacher account cannot cause teacher startup from these builds.
- Build flavour is a routing boundary, never server authorization. Future group
  APIs must enforce fresh membership and owned-group allocation independently.

No tables, endpoints, notifications, module switches or SDK operations are added.
Theme/components remain existing Material 3. No credentials or Firebase identity
are copied to a different app identity. Nonteacher native variants do not apply
Google Services/Crashlytics provisioning; their bootstrap never initializes them.
Registration and signed device/store testing remain outstanding.

Android named variants are enabled for explicit named assemble/bundle/install
commands (or -PstaffFlavors=true for IDE/task discovery). Legacy unflavoured
commands keep their established teacher outputs. iOS keeps Runner and adds shared
teacher/facilitator/vendor schemes with corresponding build configurations.

Commands from e-school-teacher:

    flutter run --flavor teacher --dart-define=FLAVOR=teacher
    flutter run --flavor facilitator --dart-define=FLAVOR=facilitator
    flutter run --flavor vendor --dart-define=FLAVOR=vendor

The latter two run the unavailable boundary only. Web builds can select FLAVOR
without a native flavour. Existing config/production.json and staging.json pin
teacher; use explicit matching defines for other build identities.

Self-test: identity/default/mismatch dispatch, unavailable screen at phone and
laptop widths, teacher suite/analyzer, native metadata/scheme validation. Android
Gradle and iOS Xcode builds require their toolchains and are recorded separately;
metadata tests are not proof of a successful native build.
