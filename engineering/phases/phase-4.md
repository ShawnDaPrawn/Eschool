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
| 4 | Polls, surveys and non-statutory elections / Backend + App | Audiences, scheduler, separate participation and responses | community/consultations.md |
| 5 | House caps/reversals, reusable awards and assembly mode / Backend + App | Groups and audited permissions | community/houses-and-awards.md |
| 6 | Signed/rotating cards, freeze, scan log, printer exports and restricted Foundation classroom entry / Backend + App | Identity; physical chip/printer qualification | community/cards.md; community/foundation-classroom.md |
| 7 | Public resumable intake, waiting list, offers and atomic enrolment / Backend + App | Profiles, guardians, approvals, Files | community/intake-admissions.md |
| 8 | Health/consent, parent meetings and gate passes / Backend + App | Restricted fields, cards and guardians | Health/consent and meeting/gate backend, private typed API, responsive panel and online reader implemented/tested in review; Parent/staff meeting clients implemented and browser-verified; CI/main integration pending |
| 9 | Consent-controlled tags and private galleries / Backend + App | Files, guardian links and transfer events | community/private-gallery.md |
| 10 | Authorized school/group live channels / Backend + App | Groups, consultations, houses; provider choice within budget | community/live-updates.md |
| 11a | School assistant and two-way translation / AI + App | Phase 3 moderated/metered AiGateway | Before implementation |
| 11b | Supervised topic-matched peer tutoring / Backend + App | Phase 3 mastery; house points | Scoped matching, supervised sessions, append-only verified service and atomic optional point corrections implemented; typed API/SDK qualified locally. Staff panel and app workflows in progress; CI/main pending |
| 11c | Subject-choice and pathway planner / Backend + App | Sourced admission requirements and marks | Reviewed school sources, immutable rules, manager maintenance and private Student/Parent planner implemented and browser-verified; CI/main pending |

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

Step 2 backend/contract is merged through PR #111: dated school-owned groups, allocations,
sessions, explicit attendance, sanitized authored materials and cascading message
audiences with older-editor compatibility. The group management panel adds
actual definition/allocation/register/material workflows with named scoped
pickers. Shared teacher/family views are delivered in PR #116. Dedicated facilitator
sign-in, atomic password/policy onboarding, fresh scoped sessions and the
restricted shared group/message shell are implemented and under final review.
The counsellor profile has no TeacherModel dependency; its restricted case/support
menus will appear only alongside their actual authorized modules. Private binary group material documents now have scanned/encrypted storage, typed API/SDK, staff panel and native reader/upload/removal flows alongside authored
Markdown. The delivery checkpoint below supersedes this early-slice status. This is not a
completed phase or a store-release qualification.

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
configuration. Public anonymous HTML reporting and code follow-up, plus the separately authorized
restricted panel, are implemented and browser-verified. Two more typed reads
provide case-owned dated professional and same-learner incident choices; report
threads now expose retained-history pagination. The restricted app's real case/triage/calendar workflows are implemented and
verified through shared tests plus both facilitator and dual-role Teacher
Chromium entrypoints. Identified learner/parent reporting is implemented and browser verified with
explicit consent and private follow-up. Private photo storage and its public/panel/API adapters now have automated and
browser evidence. Owner app photo upload and private reporter/staff thumbnails
are implemented, with full shared/family tests and learner/Parent Chrome evidence.
Generic private notice intents, outbox, fresh delivery and inbox SQL are now
implemented and verified. The dedicated Facilitator/family private inbox API and typed SDK are delivered in PR #166; the inline shared inbox now has focused tests and actual three-profile Chrome evidence. Review/CI remain separately tracked;
the module remains disabled by default until a school enables it; no deployed
reporting service is claimed. Separate support-plan/learner-note permissions never grant these cases.

Parallel ownership: the root stream owns houses/awards (step 5), then intake
(step 7) after its transfers work if still outstanding. This community stream
continues the remaining retained Phase 4 features.

Step4 consultation domain now implements scoped frozen configuration, audited
revisioned changes, physically separate anonymous ballots/participation, unique
one-person voting, validated options, scheduled opening/closure and private/public
results with bounded individual-response paging. Seventeen domain/migration cases
pass; the additive API/typed client is published in PR #183. The actual staff panel has HTTP/privacy and three-width browser evidence. Shared
readers now pass actual Teacher, Facilitator, Student and Parent Chrome flows;
opening notices pass fresh-source inbox/delivery and rollback tests. The
client/notices increment is published in PR #198 for CI and review; deployed schedules,
external delivery and device acceptance remain unverified. The root stream additionally owns personal gallery
and transfer tag-retention consent after intake.

Step 10 transport now has a qualified local Reverb/worker/socket round trip,
credential-specific leases, typed session/presence APIs and shared lifecycle
transport. Visible group presence and protected live result refreshes are implemented, with
actual dedicated staff browser transport evidence in self-tests/live-consumers.md.
Home recognition moments/counters, published CI and deployed acceptance remain
separate. No deployed latency or phase completion is claimed.


## Retained-scope delivery checkpoint: 25 October 2026

This tracks implemented software across published increments, not a claim that
all increments are merged. Integration PR #258 currently tracks subsequent review/CI and main ancestry; the earlier #203 integration is already on main.
The cancelled pilot campaign is excluded; device/store/deployed-service evidence
remains explicitly unverified and does not stop independent development.

| Retained requirement | Software delivery | Remaining implementation/evidence |
| --- | --- | --- |
| Scoped facilitator shell and native identities | Implemented Android identities, school-scoped sign-in/onboarding, fresh role/session menus | iOS native build, physical-device and store distribution evidence |
| Groups and scoped communications | Tree, dated allocations, sessions/registers, authored materials, audience cascades, messages and live presence | Encrypted/scanned binary material documents have four typed API/SDK operations, staff panel controls and Teacher/Facilitator/learner/parent readers; current-guardian consent notices and account-bound Parent actions are implemented with native/widget and actual Parent notification-to-reader browser proof; dedicated persona-bound group-chat domain is under review; its ten-operation HTTP contract, metadata-only outcome recovery and typed SDK are verified in a separate increment; app/panel conversations and guarded chat notices remain |
| Incident/counsellor workflow | Encrypted scoped casework, appointments, public/identified reports, sanitized private media, follow-up and guarded notices/inboxes | Deployment/device/external acceptance |
| Consultations | Domain, panel, four app profiles, anonymous separation and scheduled generic notices | Deployment and real delivery acceptance; statutory elections remain Phase 6 |
| Houses and awards | Caps/reversals, annual catalogue reuse, assembly, permanent awards, actual live Home counter/celebration | Deployed latency and device acceptance |
| Cards | Private identity/portrait domain, rotation/freeze/replacement, published-vector crypto, delegated online scan execution, typed API, four app profiles, staff workbench, CR80 PDFs and online scanner; bounded offline HTTP/SDK, actual three-width reader and atomic encrypted queue; restricted Teacher-delegated Foundation practice with typed HTTP and actual backend/browser qualification | NTAG encoding runtime and hardware/device acceptance |
| Intake/admissions | Public resume, encrypted/scanned applications, waiting-list offers, atomic enrolment and balanced homeroom/house choices | Staging/deployment and external acceptance |
| Health/consent, meetings and gate passes | Existing encrypted Health category reused behind fresh restricted access; immutable activity/trip consent/attestation domain, eleven private API/typed-client operations (including purpose-scoped Health files) and responsive staff publishing/permission workbench qualified | Native Parent Health/consent and staff effective-permission readers are implemented and browser-qualified; current-guardian consent notices/actions are browser-qualified; meetings/gate backend and all three app profiles are published separately by the root stream; deployed/device acceptance remains |
| Personal photo gallery | Private group/family surfaces, approval/tag withdrawal, explicit transfer retention | Deployment/device acceptance |
| Live updates | Real local Reverb/worker delivery, private credential-specific channels, scoped group presence, poll refresh and Home recognition consumers | Deployed/load/native-Reverb journey evidence; vendor queues wait for Phase 5 |
| School assistant and translation | Published school-document/office-handoff domain, source-bound inference and 21 private API operations/typed client in PRs #279/#281/#283 | Parent/office screens and sourced answer/translation journeys remain; group-chat translation must consume its dedicated authorized immutable-source adapter |
| Peer tutoring | Scoped current-topic matching, supervised sessions, append-only integer service and atomic optional house points; nine typed API operations and deterministic SDK locally qualified (#289). Staff/family shared workflows and responsive panel are implemented (#290). | Panel browser passes at 390/820/1280, shared transport/widget and tenant boundaries pass. Four actual app journeys and final retry regression are pending; CI/main and external acceptance remain separate. |
| Pathways | Reviewed immutable school sources, Grade 9 guides, source-specific integer score rules, manager panel and private Student/Parent planner are implemented and qualified (#282). | Actual panel at 390/820/1280 and complete Student/Parent Chrome journeys pass; published CI/main, school source review, fluent wording, staging/device and deployment acceptance remain separate. |

The earlier integration of 54 source PRs is now on main. Subsequent verified
increments remain in review until their exact integration head passes CI.
Hardware, store, provider and deployment acceptance remain separate from
implemented software and are not described as passed.

Parents’ evening slots and early collection: [implementation and verification](../self-tests/family-visits.md). This increment supplies the domain, typed API, staff panel and online gate workflow; Parent/staff Flutter clients are retained follow-on work.

Parent/staff meeting and collection clients are implemented in a separate review increment; [client evidence](../self-tests/family-visits-clients.md) records the passing Parent, Teacher and dedicated Facilitator browsers, shared tests and both app entry suites. This is source implementation status, not a claim that the increment has merged or all phase acceptance has passed.


Restricted Foundation classroom entry is implemented in a separate review
increment. Fresh manager/Teacher/group/device/guardian/card authority binds the
short capability; printed locators do not create gate scans or ordinary account
sessions. Fourteen focused native checks plus the deleted-year boundary pass, and
all seven browser cases pass, including real manager→Teacher→tablet practice at
390/820/1280 pixels, exact interrupted-save recovery, read-only inventory and card
withdrawal. See [executed evidence](../self-tests/foundation-classroom.md).
Canonical/SDK publication remains with the combined offline-card increment;
physical tablet/camera and school-policy acceptance are separate from this
software qualification.
