# Release artifacts

`Build release artifacts` runs on `release/**` branches or by manual dispatch.
It builds the server image and the two production web packages without changing
live services. Every artifact is named with its source commit and retained for
seven days. The server archive includes a checksum and source commit.

Production deployment still uses `Deploy production`: a release tag, a configured
`production` environment with Workload Identity Federation, a database backup and
post-deploy checks. A successful artifact build is not a successful deployment.
See [release.md](release.md) and [environments.md](environments.md).

## Android

For each app, add these **repository Actions secrets** using the existing upload
key. Do not generate a replacement for an already published app.

| Learner/parent | Teacher | Value |
| --- | --- | --- |
| `ANDROID_LEARNER_KEYSTORE_BASE64` | `ANDROID_TEACHER_KEYSTORE_BASE64` | Base64-encoded upload keystore |
| `ANDROID_LEARNER_STORE_PASSWORD` | `ANDROID_TEACHER_STORE_PASSWORD` | Keystore password |
| `ANDROID_LEARNER_KEY_ALIAS` | `ANDROID_TEACHER_KEY_ALIAS` | Signing key alias |
| `ANDROID_LEARNER_KEY_PASSWORD` | `ANDROID_TEACHER_KEY_PASSWORD` | Signing key password |

When signing is configured the workflow builds a signed APK and AAB using each
app's production configuration and current pubspec version. It removes temporary
signing files afterwards. When a secret is absent it records the exact missing
names and produces no Android package. A green overall run can therefore still
have blocked Android releases; read both Android job summaries. Uploading the
AAB to Google Play remains a separate distribution step.

## iOS

The workflow uses the macOS26 runner with Xcode26.6 (explicit DEVELOPER_DIR)
and compiles both production apps with `--no-codesign` and labels their
archives **unsigned**. These are build evidence, not installable IPAs. Neither
project currently has an Apple development team configured. A signed App Store
or TestFlight release needs the owner's Apple team, distribution certificate,
matching provisioning profiles and App Store Connect access; none are invented
or replaced by this workflow.

## Release status on 24 October 2026

- The owner requested releases for the website/admin and both apps.
- Docs have a working Firebase credential and an approved release request.
- Cloud Run deployment authentication is not configured: no `production` or
  `staging` GitHub environment was present during inspection. The deployment
  workflow requires `GCP_PROJECT_ID`, `GCP_WIF_PROVIDER` and `GCP_DEPLOY_SA`.
- No staging deployment, production backup, migration rehearsal, authenticated
  live smoke test or mobile store publication is claimed by an artifact build.
- The two configured portal Hosting sites returned 404. Publish their new web
  packages only after the matching backend has deployed and passed its checks.

The first artifact run failed both iOS jobs on the older macOS15 toolchain.
The learner Stripe plugin uses UIKit APIs absent from that SDK; the teacher
job's summary reported a device-build failure and a generic signing hint.
The updated job records Xcode's version and retains verbose failure logs;
only successful compilation counts as evidence. No signing team is fabricated.

Verbose logs isolated the teacher failure to the Crashlytics symbol-upload
build phase calling an uninstalled flutterfire CLI. Compilation artifacts now
set SKIP_CRASHLYTICS_SYMBOL_UPLOAD=1; both project scripts skip only that upload
when the explicit flag is present. Ordinary signed builds keep their symbol
upload and require the FlutterFire CLI. Both shell branches were checked locally;
the actual unsigned device compilations still must succeed in Actions.
