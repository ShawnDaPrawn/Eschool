# Phase 2: academic core

## Current completion scope

On 24 October 2026 the owner cancelled the pilot stage and asked for Phase 2 to
finish today. The retained scope is academic software, communications, exports,
languages and shared desktop screens, with safe integration into main.

Pilot onboarding/import tooling, pilot champion feedback/status tooling and the
full-term parallel production run are no longer Phase 2 deliverables or completion
conditions. The existing `marks:reconcile` command remains available. This change
supersedes the earlier pilot campaign; it does not remove records or authorize
production deployment, extra spending or a claim that unrun checks passed.

## Implementation register

| Retained work | Delivery |
| --- | --- |
| Grades/phases/ratings and class mapping; school terms/templates | #81, #82 |
| Subject standards and versioned curriculum/assessment plans | #86, #87 |
| Dated teaching groups and allocations | #88 |
| Exact task/question marks, moderation, locks and reconciliation; shared capture grid | #89, #90 |
| Rooms, bells, clash-aware timetables and shared week/editor screens | #91, #92 |
| Registers, QR, corrections, absence alerts and offline sync | #93, #94 |
| Report designs/comments, approvals, queued PDFs and family readers | #95, #96 |
| Dated joins, departures, subject changes and private signed exit packages | #97 |
| Private conversion, Markdown readers and encrypted leased offline copies | #98, #99, #100 |
| Audiences, role approval, scheduling, private delivery/read state and panel | #101 |
| Shared phone/two-pane message readers, composition and separate-author review | #102 |
| Private versioned learner/attendance/task-mark exports | #103 |
| Afrikaans/isiZulu files, shared academic localization and structural review | Language closeout PR |

The implementation is present in the corresponding feature branches/PRs. GitHub
records final-head CI, review resolution and merge status; completion requires the
retained source to reach main safely. Semester compatibility is retired under the
owner's earlier decision; unrelated v1 contracts remain protected.

## Verification and release boundaries

Results are recorded in [the self-test record](../self-tests/phase-2.md) and
[security evidence](../security/phase-2.md). Known data loss, school exposure,
authentication or unusable-build risks block the affected change. Relevant
regressions are fixed and required CI passes before merge.

The export bridge uses operator-supplied exact layouts/templates; a generated
file is not certification of an actual SA-SAMS import. Language files are complete
development drafts with automated placeholder/key checks and a working glossary;
[fluent review](../languages/review.md) remains explicit. Staging load/UX,
physical-device/provider and independent security evidence are also separate
release evidence. None is fabricated or silently converted to a passed pilot.
English stays the default. No production release or additional spending above
the existing US$10/month staging/email ceiling is authorized.
