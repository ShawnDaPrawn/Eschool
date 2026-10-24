# Phase 2: academic core and pilot

Phase 2 starts with independent, additive foundations while Phase 0/1 acceptance
and remaining implementation stay open. Starting this phase does not certify
those phases, authorize a production release, or raise the US$10/month additional
staging/email budget.

## Delivery order

1. Shared grades R–12, school phases and seven-point rating references; explicit,
   authorized mapping of existing classes. This first slice does not infer grade
   from class names or change existing grading/report calculations.
2. School/year/grade calendars and templates, replacing semesters in place under the owner
   decision of 23 October; update year preparation and academic consumers together.
3. Confirmed subject catalogue and assessment ratios, versioned curriculum and
   assessment plans, then task/question marks and audited moderation/locks.
4. Dated teaching groups, rooms and clash-aware timetable; register and offline
   attendance, corrections, mid-year learner and subject transitions.
5. Reports and release approvals, private content conversion, audience messages,
   exports, translations and shared desktop academic screens.
6. Reconciliation tools, import rehearsal and full-term parallel pilot evidence.

Each feature receives its own spec, tests and reviewable PR. Independent work
continues while decisions or operational evidence for another feature are pending.
Data loss, school leakage, authentication defects or unusable builds block the
affected change. Mark weighting, rounding and subject standards require confirmed
rules before they drive learner results.

## Status

The first slice is specified in [academic-references](../specs/academics/academic-references.md).
Automated results, staging checks and acceptance are recorded separately in
[the self-test record](../self-tests/phase-2.md). Academic references/class mapping
and school terms/templates are merged (#81/#82). Subject standards/mapping and curriculum plans are merged (#86/#87); dated teaching structures are merged (#88); the exact marks backend is merged (#89); shared mark clients are merged (#90); timetable services are merged (#91); shared timetable clients are merged (#92); attendance services are under verification; remaining academic workflows and external pilot
evidence are tracked below.
Semester compatibility is retired by the owner decision; existing IDs and academic
links migrate to terms. Other grading and exam/result contracts stay protected.

## Remaining implementation campaign (24 October 2026)

Owner instruction: complete the rest of Phase 2 autonomously, returning to the
private Build Bible for uncertainty. References/class mapping and school terms
are merged. Continue feature branches in this order:

- Subject catalogue/mapping and versioned curriculum/assessment plans.
- Allocations/grade-head authority, exact mark calculations, capture/moderation/locks
  and immutable snapshots, with existing result consumers preserved.
- Rooms, bell schedules, clash-aware timetable and dated groups/transitions.
- Homeroom/period/QR attendance, corrections, alerts and offline sync.
- Report templates/comments, approvals, queued PDFs and private release.
- Private conversion/content, audiences/approved scheduled messages.
- Shared adaptive academic clients, reviewed language infrastructure and exports.
- Reconciliation/import rehearsal, feedback/status, security/performance/UX checks.

Track each feature against its spec and distinguish implementation, automated
verification, staging, pilot and production. The Bible requires an actual full
school term of parallel evidence and independent security review; those cannot be
substituted with generated fixtures. Continue all independent software work.
No production release or spending above the existing US$10/month staging/email
cap is authorized by the overnight instruction.
