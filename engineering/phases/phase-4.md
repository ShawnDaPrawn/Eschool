# Phase 4: facilitator and community

The owner authorized Phase 3 and Phase 4 concurrently on 24 October 2026.
Continue independent features while acceptance evidence is unavailable. Production
release and spending limits are unchanged; pilot onboarding remains cancelled.

| Order | Feature / owner | Dependencies | Specification |
| --- | --- | --- | --- |
| 1a | Native staff build identities / App | Existing teacher shell | community/staff-build-identities.md |
| 1b | Scoped facilitator role, API and menu / Backend + App | Dated owned groups from step 2 | community/facilitator-access.md |
| 2 | Activity tree, seasons, dated members/staff, sessions, materials and cascading audiences / Backend + App | Phase 2 Files, Notifier and audiences | activities/groups.md; activities/group-surfaces.md |
| 3 | Anonymous incidents, token follow-up, restricted counselling cases and appointments / Backend + App | Shared safeguarding policy with Phase 3; no impersonation access | community/wellbeing.md |
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

Step 1a is merged: native identities, fail-closed bootstrap and named Android
APK CI. Native iOS configurations were validated structurally; no Xcode build was
available. Vendor identities remain unavailable. The facilitator sign-in and scoped shell
are implemented in the current access feature; review/CI and external native
qualification remain separately recorded. Native teacher identity initialization
can occur before a mismatched Dart flavour is rejected.

Step 2 backend/contract is in PR #111: dated school-owned groups, allocations,
sessions, explicit attendance, sanitized authored materials and cascading message
audiences with older-editor compatibility. The group management panel adds
actual definition/allocation/register/material workflows with named scoped
pickers. Shared teacher/family views are delivered in PR #116. Dedicated facilitator
sign-in, atomic password/policy onboarding, fresh scoped sessions and the
restricted shared group/message shell are implemented and under final review.
The counsellor profile has no TeacherModel dependency; its restricted case/support
menus will appear only alongside their actual authorized modules. Private binary group material uploads remain a follow-up to authored
Markdown. Other Phase 4 features remain pending in this table; this is not a
completed phase or usable facilitator store app.

All new endpoints will require wrong-role, wrong-school and wrong-owner cases.
Case privacy excludes platform staff and impersonation. Anonymous reports retain
no reporter/device/IP identifiers and strip media metadata. Votes have a database
one-person constraint, with anonymous response separation. Cards, intake,
consent, live-channel authorization and app flows have the Bible's relevant
security and profile checks. Record actual results in self-tests/phase-4.md.
Device/card/provider, staging performance, user acceptance and production approval
are separate evidence; no cancelled pilot run or unrun check is called passed.


Wellbeing domain services are implemented in the current feature branch: separate
team/case assignments, anonymous secret follow-up, explicit identification, encrypted
case history and appointments. Thirty-one typed HTTP operations and their
generated SDK now implement actual scoped reporting, triage, casework and metadata
configuration. Public HTML, private media, panel and app views remain in active
follow-up work; the disabled module is not yet a completed user
workflow. Separate support-plan/learner-note permissions never grant these cases.
