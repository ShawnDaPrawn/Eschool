# Concurrent Phase 3 and Phase 4 delivery

## Owner decision and baseline

On 24 October 2026 the owner authorized two agents to start Phase 3 and Phase 4
concurrently. The private Build Bible explicitly schedules these phases in
parallel. Baseline main is `9fc30e42fc7638442d43327648195acdf976eb40`; all 21
Phase 2 implementation PRs were verified merged, with no open Eschool PRs.
Phase 2's source integration is complete; its unrun external acceptance evidence
remains recorded separately. The cancelled pilot campaign is not reintroduced.

One agent owns each phase in an isolated worktree. The lead coordinates shared
contracts, reviews source and evidence, and integrates small feature PRs through
green CI. Starting these streams does not assert either phase is complete.

## First independent deliveries

| Stream | Initial branch | First reviewable slice |
| --- | --- | --- |
| Phase 3: AI learning and pastoral care | `feat/p3-ai-foundation` | Service/CLI inference gateway foundation; preserve existing private content transcription |
| Phase 4: facilitator and community | `feat/p4-facilitator-foundation` | Native build identities and startup isolation; preserve the existing teacher build |

Phase 3 owns its plan, questions, AI specifications, gateway implementation and
verification record. Phase 4 owns its plan, questions, community specifications,
flavour implementation and verification record. The lead owns this document,
AGENTS.md and the shared Build log. Each agent reads the full relevant private
Bible section before implementing; the document itself is never published.

The first gateway slice has no new public API or generated SDK operations. The
first flavour slice has no new school permissions or group APIs. An unavailable
facilitator/vendor build must not start the teacher application or its auth,
storage, network or deep-link initialization. Native metadata and unit checks
do not establish an Android/iOS store release or a complete facilitator app.

## Feature dependencies

| Consumer | Required producer/contract | Independent work continues |
| --- | --- | --- |
| Phase 4 school assistant and message translation | Phase 3 moderated, metered AiGateway with approved source access | Groups, facilitator identity, consultations, awards, intake and forms |
| Phase 4 peer matching | Phase 3 topic graph and MasteryService | Supervision/session data and house/service-hour rules |
| Phase 3 grade-head award nominations | Phase 4 award categories and scoped administrators | Grade-head academic/attendance/discipline work |
| Counselling, safeguarding, support and risk features | Shared explicit access and retention contract | Unrelated academic and community modules |
| Phase 4 gallery consent on transfer | Phase 3 transfer lifecycle and consent hook | Private gallery/tagging and signed transfer implementation |
| Cards, intake fees and later vendor capabilities | Phase 5 wallet/payment integration | Nonpayment cards and intake; office-recorded fee status where specified |

Only affected features wait for missing contracts or external decisions. A phase
checkpoint does not freeze independent development.

## Shared rules

- Extend existing Laravel, Flutter, module, Files and Notifier boundaries; retain
  unrelated v1 contracts and Phase 2 behavior. New HTTP capabilities use additive,
  contract-tested v2 endpoints. A build flavour grants no server permission.
- Membership, module availability and authority are rechecked in the current
  school before sensitive reads/writes or replay. Facilitators receive access
  only to their assigned active groups, never inherited teacher-wide access.
- Existing dated academic leadership remains the authority for grade heads;
  avoid a second competing grade-head model. Future awards use that authority.
- Counselling notes, cases, interventions and safeguarding content require
  explicit role/capability checks. Platform access, impersonation and generic
  school-admin permissions must not expose restricted case contents. Ordinary
  profile-field permissions are not a substitute for a case policy.
- Anonymous reporting needs an end-to-end privacy contract: request/audit logs,
  identifying columns, media metadata and follow-up tokens all count. Do not
  claim anonymity merely because a reporter column is null.
- Route inference through AiGateway with versioned prompts, input/output
  moderation, per-school/learner caps, integer cost accounting and evaluations.
  External provider activation, processing locations, privacy terms and live
  safety/cost evidence remain explicit decisions. No paid provider is activated
  by this kickoff, and no real-provider evaluation is inferred from test doubles.
- Shared route registries, role seeds, navigation/module catalogues, OpenAPI and
  generated SDK changes are coordinated before publication. Generate the SDK
  from one reviewed combined contract at a time. Reserve unique migration times
  and use descriptive names; do not edit another agent's source worktree.
- Record tests actually run, source review, staging/device evidence and external
  acceptance separately. Each feature covers happy paths and wrong role, school
  and owner, including indirect files/notification access where applicable.
- Merge each feature only after relevant review and green required CI. Preserve
  the existing US$10/month additional staging/email ceiling. Production, store
  submissions and provider spending are not authorized by this kickoff.

## Integration procedure

Publish one feature PR per slice. The lead reviews its diff against its spec,
checks tenant/authentication boundaries and actual verification results, then
merges with an expected-head lease after required CI succeeds. Update each
stream from main before its next feature; resolve shared-file changes explicitly.
Keep implementation, integration and external acceptance status distinct in the
phase plans and verification records. Hardware, fluent review, source licences
and live-provider evidence are never replaced with fabricated passes.


The lead additionally owns the Phase 4 house-points/awards increment and then
intake/admissions, while the Phase 4 agent continues wellbeing, consultations,
cards, forms, gallery and live/community consumers. These use the existing shared
identity, dated academic authority and private-file services. Gallery tag handling
must subscribe to completed transfers; retaining tags needs an explicit guardian
choice bound to that specific signed consent. It must not extend old-school
membership or generic image access. That integration remains subsequent gallery
work while this transfer increment is reviewed.


## Closing checkpoint and bounded preparation, 26 October 2026

Both retained software streams are merged in main `990ea2a7b`; the current
[Phase 3](phase-3.md) and [Phase 4](phase-4.md) records supersede the original
branch/pending statuses above. The exact closing integration passed all 106
expected CI jobs and its separate Security, Staff identity and Firebase checks.
External evidence remains explicit rather than inferred from that result.

The owner accepted bounded preparation before Phase 5. Recovery/retention and
joined screen-state review, existing fee/ledger boundary corrections and the
admissions/transfer/leadership financial handover are separate reviewable
increments. Contracts, shared permissions and source ownership remain coordinated.
These preparation records do not authorize new payments, live providers or
production activation. Ordinary group-chat translation remains distinct from
the completed original-preserving Parent-office message translation scope.
