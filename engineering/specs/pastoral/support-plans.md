# Restricted SIAS support plans

A current dated principal appoints named counsellors/safeguarding professionals
to a learner's support caseload. Appointment management exposes only assignment
metadata and never grants the principal plan contents. Actual plan access also
requires fresh restricted pastoral role/capability, current school/module and an
active dated caseload assignment. Platform identities and impersonation are
excluded even with another school role. School-admin/profile permissions alone
cannot read a plan.

Plans retain a school-recorded SIAS stage, needs, accommodations and immutable
reviews and encrypted immutable plan versions. Stage labels follow the school's actual SIAS process; the application
does not invent official progression rules. Plan content, review text and
assignment rationale are encrypted. Every mutation uses a pinned school lock,
UUID/HMAC receipt, optimistic revision and fresh authority before replay and
after mutation. Closing/revoking retains history. Retained records prevent
destructive rollback. Plans can span school years; their actual dates and
creation/review timestamps remain distinct.

An appointed professional may explicitly disclose selected accommodations to a
named current teacher, with an expiry date. That separate projection contains
only the approved accommodation text and learner identity, without needs,
counselling/review text or case files. A teacher never reads the encrypted plan
body to construct this response. Fresh teacher role, recipient, expiry, current
module and unwithdrawn plan/disclosure are checked on each read. Disclosure does
not grant academic, ordinary profile, other-case or permanent-record access.

Learners/guardians do not automatically receive these restricted records. No
support text feeds the tutor, principal aggregates, search, notifications,
ordinary profile fields or transfer packages. A clinician/teacher can continue
independent work while external safeguarding/device acceptance remains pending;
those acceptance checks are never inferred from fixtures.

Required verification includes case-owner/school/role isolation, operator and
impersonation refusal, grant/role/module withdrawal before replay, teacher
projection redaction/revocation/expiry, encrypted rows and audit redaction,
context-change rollback, revisions/UUID collisions, append-only reviews and
rollback retention. Scoped API, panel and shared phone/desktop views are required
to finish the feature.

Changing the actual accommodations invalidates previous teacher disclosures and
requires explicit new approval. Reviews alone retain the approved instructions.
Teacher projections also require a currently enrolled learner; professional
history remains retained under the live caseload policy.
