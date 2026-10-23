# Phase 1 verification record

Development continues while staging/device evidence is pending. Do not infer
acceptance or production release from this record.

| Feature | Profile | Environment | Check | Result | Evidence |
| --- | --- | --- | --- | --- | --- |
| Guardian approvals | Parent, learner, teacher; two schools | Local PHP 8.3 / MariaDB | Approve, repeat, withdraw, wrong owner/school/role, stale consent, removed links and token checks | Automated pass in focused run | `GuardianApprovalTest` |
| Guardian invitation | Parent, school staff; two schools | Local PHP / MariaDB | Encrypted secret, preview, verify mailbox, choose password, one-use acceptance, expiry, replacement and reminders | Automated pass in focused run | `GuardianInvitationTest` |
| Approval screen | Parent | Flutter Linux widget harness | Shared screen and API repository behaviour | 44 package tests pass | `eschool_people/test/` |
| Enrolment history and numbers | Office, parent, learner, teacher; two schools | Local PHP 8.3 / MariaDB | Checksum, global uniqueness, one human at two schools, dated and scheduled moves, retry-safe backfill, tenant/role denial, legacy login and deletion safety | Focused tests passed; full CI required before merge | `EnrolmentHistoryTest`, `AcademicAuthorizationTest` |
| Child identity cache | Parent | Flutter Linux widget harness | Generated child contract, old sign-in records and cache round-trip | 94 session tests passed | `eschool_session/test/` |
| Module controls | School office, parent, learner, teacher; two schools | Local PHP / MariaDB | Package denial, required features, dependencies, tenant isolation, money read-only, browser/native separation and silent refresh queue | Focused backend checks passed; full CI pending | `ModuleControlsTest` |
| Module visibility | Learner/parent and teacher | Flutter Linux widget harness | Old-server compatibility, context refresh and disabled direct routes | 97 session tests passed; app suites 42 and 25 passed | `module_access_test.dart` and app suites |
| Handles | Learner, adult, guardian, teacher; two schools | Local PHP / MariaDB | Guardian review, replay/expiry, wrong school/owner/role, private identifiers, global reservations and sign-in | Full backend run: 775 passed; final identity/deletion run: 53 passed; CI pending | `HandlesTest` |
| Handle settings | Learner and guardian | Flutter Linux widget harness | Propose, approve/reject, busy state, preserved failed input, email verification and old-server behaviour | People package: 51 tests passed | `handles_test.dart` |
| Handle request flow | Parent | Local headless Chromium | Sign in, open settings, approve proposed handle, verify decision request and refreshed list | Passed | `handles_smoke_test.dart` |
| Live onboarding | Parent and learner | Staging, Android/iOS | Real email through first sign-in and consent | Pending: environment and device access | No staging run claimed |
| First-time UX | All affected profiles | Staging | Timed unaided tasks | Pending: owner review first; testers unavailable | No acceptance claimed |
