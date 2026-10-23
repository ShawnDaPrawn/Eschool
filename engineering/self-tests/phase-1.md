# Phase 1 verification record

Development continues while staging/device evidence is pending. Do not infer
acceptance or production release from this record.

| Feature | Profile | Environment | Check | Result | Evidence |
| --- | --- | --- | --- | --- | --- |
| Guardian approvals | Parent, learner, teacher; two schools | Local PHP 8.3 / MariaDB | Approve, repeat, withdraw, wrong owner/school/role, stale consent, removed links and token checks | Automated pass in focused run | `GuardianApprovalTest` |
| Guardian invitation | Parent, school staff; two schools | Local PHP / MariaDB | Encrypted secret, preview, verify mailbox, choose password, one-use acceptance, expiry, replacement and reminders | Automated pass in focused run | `GuardianInvitationTest` |
| Approval screen | Parent | Flutter Linux widget harness | Shared screen and API repository behaviour | 44 package tests pass | `eschool_people/test/` |
| Live onboarding | Parent and learner | Staging, Android/iOS | Real email through first sign-in and consent | Pending: environment and device access | No staging run claimed |
| First-time UX | All affected profiles | Staging | Timed unaided tasks | Pending: owner review first; testers unavailable | No acceptance claimed |
