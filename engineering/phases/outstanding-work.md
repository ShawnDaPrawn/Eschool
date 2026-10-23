# Phase 0 and 1 delivery register

Updated 23 October 2026. Acceptance is a checkpoint, not a freeze on independent
work. A checked-in feature is not proof of staging verification or acceptance.

| Work | Implementation | Automated verification | Staging / acceptance | Next action |
| --- | --- | --- | --- | --- |
| Phase 0 foundations | Merged | Dependency integration checks passed before merge | Pending | Reconcile remaining operational evidence |
| Learner Android smoke on main | Emulator job failed after merge | Fresh main retry passed; pinned-host PR checks passed | Not applicable | Merged in PR #66 |
| Tenancy and memberships | Merged | Existing two-school tests | Pending | Extend for each new domain |
| Guardian approval and invitations | Merged in PR #67; rollout disabled pending family transition | All PR CI checks passed | Pending | Verify real email onboarding and family transition |
| Handles and modern sign-in | Pending | Pending | Pending | Continue remaining identity work |
| Enrolment history and student numbers | Merged in PR #68 | 731 backend tests and all 24 PR checks passed | Pending | Rehearse backfill on isolated staging; continue dated report consumers and staff allocation history |
| School module controls | Implemented on feature branch | Focused backend and Flutter checks passed; full CI pending | Pending | Review and merge module controls |
| Other school configuration and notifications | Pending | Pending | Pending | Build notification rules, profile fields and school documents |
| SaaS onboarding, imports and privacy | Pending | Pending | Pending | Build platform tools and restricted support access |
| Modern sign-in | Pending | Pending | Provider/device checks pending | Add passkeys, verified-domain OAuth and parent links |
| Staging infrastructure and mail | Definitions exist | Dry-run evidence only | Blocked: credentials and cost feasibility | Inventory resources and credits; do not exceed US$10/month |
| Restore drill and worker observation | Runbooks exist | Not run in cloud | Pending | Isolate restore destination before copying data; observe worker seven days |
| UX and owner acceptance | Local Phase 0 evidence exists | Does not substitute for UX participants | Owner review first; first-time testers unavailable | Record review separately and schedule testers when available |

Existing product defaults are confirmed. Phase 1 implementation may proceed
while Phase 0's external evidence is pending. No production deployment, secret
rotation or production data deletion is implied by this register.
