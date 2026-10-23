# Phase 0 and 1 delivery register

Updated 23 October 2026. Acceptance is a checkpoint, not a freeze on independent
work. A checked-in feature is not proof of staging verification or acceptance.

| Work | Implementation | Automated verification | Staging / acceptance | Next action |
| --- | --- | --- | --- | --- |
| Phase 0 foundations | Merged | Dependency integration checks passed before merge | Pending | Reconcile remaining operational evidence |
| Learner Android smoke on main | Emulator job failed after merge | Fresh main retry passed; pinned-host PR checks passed | Not applicable | Merged in PR #66 |
| Tenancy and memberships | Merged | Existing two-school tests | Pending | Extend for each new domain |
| Guardian approval and invitations | Merged in PR #67; invitation lifecycle hardening on feature branch; rollout disabled pending family transition | 144 combined-main backend tests passed; Pint, Larastan and docs passed; PR CI pending | Pending | Merge mailbox binding fix; verify real email onboarding and family transition |
| Handles | Merged in PR #70 | All 25 PR checks passed, including guardian browser flow and Android | Pending | Verify guardian workflow and notifications on staging |
| Enrolment history and student numbers | Merged in PR #68 | 731 backend tests and all 24 PR checks passed | Pending | Rehearse backfill on isolated staging; continue dated report consumers and staff allocation history |
| School module controls | Merged in PR #69 | All 24 PR checks passed, including the browser settings flow and Android | Pending | Verify live push and portal behaviour on staging |
| Notification rules and delivery | Merged in PR #71 | All 25 PR checks passed, including Android and browser flows | Pending | Verify real mail/push |
| Other school configuration | Pending | Pending | Pending | Build profile fields and school documents |
| SaaS onboarding, imports and privacy | Pending | Pending | Pending | Build platform tools and restricted support access |
| Modern sign-in | Pending | Pending | Provider/device checks pending | Add passkeys, verified-domain OAuth and parent links |
| Staging infrastructure and mail | Definitions exist | Dry-run evidence only | Blocked: credentials and cost feasibility | Inventory resources and credits; do not exceed US$10/month |
| Restore drill and worker observation | Restore quarantine procedure corrected on feature branch | Shell examples parsed; sanitisation SQL passed 9 assertions on separate local MariaDB; cloud drill not run | Pending | Verify budget and quarantine in cloud; rehearse restore and observe worker seven days |
| UX and owner acceptance | Local Phase 0 evidence exists | Does not substitute for UX participants | Owner review first; first-time testers unavailable | Record review separately and schedule testers when available |

Existing product defaults are confirmed. Phase 1 implementation may proceed
while Phase 0's external evidence is pending. No production deployment, secret
rotation or production data deletion is implied by this register.
