# Phase 4: facilitator and community

The owner authorized Phase 3 and Phase 4 concurrently on 24 October 2026.
Continue independent features while acceptance evidence is unavailable. Production
release and spending limits are unchanged; pilot onboarding remains cancelled.

| Order | Feature / owner | Dependencies | Specification |
| --- | --- | --- | --- |
| 1a | Native staff build identities / App | Existing teacher shell | community/staff-build-identities.md |
| 1b | Scoped facilitator role, API and menu / Backend + App | Dated owned groups from step 2 | Next feature specification |
| 2 | Activity tree, seasons, dated members/staff, sessions, materials and cascading audiences / Backend + App | Phase 2 Files, Notifier and audiences | Before implementation |
| 3 | Anonymous incidents, token follow-up, restricted counselling cases and appointments / Backend + App | Shared safeguarding policy with Phase 3; no impersonation access | Before implementation |
| 4 | Polls, surveys and non-statutory elections / Backend + App | Audiences, scheduler, separate participation and responses | Before implementation |
| 5 | House caps/reversals, reusable awards and assembly mode / Backend + App | Groups and audited permissions | Before implementation |
| 6 | Signed/rotating cards, freeze, scan log and printer exports / Backend + App | Identity; physical chip/printer qualification | Before implementation |
| 7 | Public resumable intake, waiting list, offers and atomic enrolment / Backend + App | Profiles, guardians, approvals, Files | Before implementation |
| 8 | Health/consent, parent meetings and gate passes / Backend + App | Restricted fields, cards and guardians | Before implementation |
| 9 | Consent-controlled tags and private galleries / Backend + App | Files, guardian links and transfer events | Before implementation |
| 10 | Authorized school/group live channels / Backend + App | Groups, consultations, houses; provider choice within budget | Before implementation |
| 11a | School assistant and two-way translation / AI + App | Phase 3 moderated/metered AiGateway | Before implementation |
| 11b | Supervised topic-matched peer tutoring / Backend + App | Phase 3 mastery; house points | Before implementation |
| 11c | Subject-choice and pathway planner / Backend + App | Sourced admission requirements and marks | Before implementation |

Each feature gets a scoped spec, tests and small PR; table order permits independent
work, not an acceptance freeze. Phase 3 owns AiGateway/mastery. Phase 4 owns group
and award contracts. Counsellor/safeguarding policy is agreed before either stream
adds sensitive records. API contract/SDK and migration edits are coordinated by
the lead. No placeholder endpoint or menu claims unimplemented functionality.

## Current slice and verification

Only 1a is in progress: build identities and a fail-closed bootstrap boundary.
Facilitator/vendor builds cannot enter the teacher shell or initialize Firebase,
auth/session storage, notifications, routes or repositories. They are unavailable
until server roles and owned-group workflows exist. This is not a usable
facilitator product or a completed Phase 4. Teacher startup remains supported.

All new endpoints will require wrong-role, wrong-school and wrong-owner cases.
Case privacy excludes platform staff and impersonation. Anonymous reports retain
no reporter/device/IP identifiers and strip media metadata. Votes have a database
one-person constraint, with anonymous response separation. Cards, intake,
consent, live-channel authorization and app flows have the Bible's relevant
security and profile checks. Record actual results in self-tests/phase-4.md.
Device/card/provider, staging performance, user acceptance and production approval
are separate evidence; no cancelled pilot run or unrun check is called passed.
