# Phase 0 and 1 delivery register

Updated 23 October 2026. Acceptance is a checkpoint, not a freeze on independent
work. A checked-in feature is not proof of staging verification or acceptance.

| Work | Implementation | Automated verification | Staging / acceptance | Next action |
| --- | --- | --- | --- | --- |
| Phase 0 foundations | Merged | Dependency integration checks passed before merge | Pending | Reconcile remaining operational evidence |
| Learner Android smoke on main | Emulator job failed after merge | Fresh main retry passed; pinned-host PR checks passed | Not applicable | Merged in PR #66 |
| Tenancy and memberships | Merged | Existing two-school tests | Pending | Extend for each new domain |
| Guardian approval and invitations | Merged in PRs #67 and #72; legacy child response correction merged in #74; rollout disabled pending family transition | All 25 checks passed on each hardening PR; local ownership, mailbox and v1/v2 regressions passed | Pending | Verify real email onboarding and family transition |
| Handles | Merged in PR #70 | All 25 PR checks passed, including guardian browser flow and Android | Pending | Verify guardian workflow and notifications on staging |
| Enrolment history and student numbers | Merged in PR #68 | 731 backend tests and all 24 PR checks passed | Pending | Rehearse backfill on isolated staging; continue dated report consumers and staff allocation history |
| School module controls | Merged in PR #69 | All 24 PR checks passed, including the browser settings flow and Android | Pending | Verify live push and portal behaviour on staging |
| Notification rules and delivery | Merged in PR #71 | All 25 PR checks passed, including Android and browser flows | Pending | Verify real mail/push |
| Configurable profiles | Schema/API merged in #75; panel/app editor, metadata context and permitted export merged in #76; private file flow in PR #78 | All 26 checks passed on #76, including 906 backend tests, browser/accessibility, Chrome and both Android suites | Pending | Complete CI/merge for #78; continue legacy bridge and storage retirement |
| Other school configuration | My school backend/editor in PR #77; uses shared app screen and existing branding/term records | Focused contracts and school/role/publishing checks pass; browser/CI results recorded with feature | Pending | Merge contacts/documents/branding flow; continue avatar/photo persistence |
| SaaS onboarding, imports and privacy | Pending | Pending | Pending | Build platform tools and restricted support access |
| Modern sign-in | Pending | Pending | Provider/device checks pending | Add passkeys, verified-domain OAuth and parent links |
| Staging infrastructure and mail | Definitions exist | Dry-run evidence only | Blocked: credentials and cost feasibility | Inventory resources and credits; do not exceed US$10/month |
| Restore drill and worker observation | Restore quarantine procedure merged in PR #73 | Shell examples parsed; sanitisation SQL passed 9 assertions on separate local MariaDB; cloud drill not run | Pending | Verify budget and quarantine in cloud; rehearse restore and observe worker seven days |
| UX and owner acceptance | Local Phase 0 evidence exists | Does not substitute for UX participants | Owner review first; first-time testers unavailable | Record review separately and schedule testers when available |

Existing product defaults are confirmed. Phase 1 implementation may proceed
while Phase 0's external evidence is pending. No production deployment, secret
rotation or production data deletion is implied by this register.
