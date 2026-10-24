# Mid-year joins, leaves and subject changes

Build Bible Phase 2: each dated change has one office workflow and a dedicated
service, built on canonical enrolments, teaching memberships, marks and reports.
No change activates a learner account or bypasses guardian approval. Other schools
and the same person's parent/staff roles remain usable.

Office authority is the school's academic-structure-manage permission; exit
exports additionally require profile-sensitive-export. All reads/writes recheck
fresh school/person scope. Preview shows the dated enrolment, affected group
memberships, grade policy, selected timetable assignments and scheduled rows that
will end or be cancelled. Commit requires that exact current-state fingerprint
and a stable actor/school-bound operation UUID. The operation, encrypted reason,
audit, projections and outbox commit together. Retries return the original result;
a changed request or competing state requires review rather than automatic rebase.

Join appends an enrolment on its first school day, chooses dated teaching groups
and records an explicit fee pro-rata request flag. No invoice/payment is posted.
School grade defaults choose catch-up or excused for tasks before joining. Existing
account and approval state are preserved; group/homeroom membership naturally
selects the timetable. A learner already enrolled on that date uses the existing
homeroom move or subject-change workflow instead of a duplicate join.

Leave records the last attended date, closes covering enrolments/memberships and
cancels scheduled later enrolments/memberships without erasing their history.
Learner app entry and requests enforce the dated departure, including before a
scheduled projection job runs; old imports cannot silently reactivate a declared
leave. A later explicit join opens access only from its date, with guardian
approval still required. Student session projections follow the date. Do not
change global user status or revoke another school's/role's sessions.

Subject change closes a dated source membership and appends the target membership.
Per-grade defaults are school-configurable; a privileged office override is explicit
and audited. Catch-up/excused affect earlier uncaptured tasks. Carried marks apply
only between groups sharing subject, grade and year: existing canonical task marks
are reused without copying/rounding. Different subjects require catch-up/excused;
no unrelated score is invented. Captured/released evidence stays intact. Same-day
cancellation of an allocation holding captured marks requires a later effective
date so the original membership/history remains reachable.

An exit package is a queued private ZIP containing own released report PDFs,
released mark summaries, captured attendance and enrolment history, plus explicitly
selected current profile documents the requester may export. Preview pins source
IDs/checksums and bounds size/count. A changed source, unavailable selected file or
revoked requester fails the job; no partial package is represented as complete.
The manifest includes per-file checksums and a detached school export signature.
Download links expire, bind reader/school and recheck authority. Scratch files are
private and removed in finally blocks; stored artifacts are encrypted.

Export copies expire after a school-configurable 1–30 days (default 7). A nightly
retention job removes expired copies and private package payloads, retaining only
a minimal receipt/audit. Canonical educational records are retained: no unapproved
retention period or deletion rule is invented. The missing wider Phase 1 retention
schedule remains a separate policy/kernel acceptance item; leaving never deletes
marks, reports, identity or guardian history. The export copy rule is explicit in
retention_rules and does not authorize deletion of those source tables.

The panel provides learner search/history and dedicated join/leave/subject forms,
grade defaults, preview/review and queued exit export/download. It uses M3 tokens,
explicit dates and private/no-store responses. API v2 has typed contracts and SDK;
v1 fields remain intact. New cancellation state is additive to enrolment history.

Verification covers current/future/past boundaries, stale/duplicate/revoked retries,
same-school wrong person and cross-school IDs, every mark rule, future cancellation,
retained reports, named learner tokens versus other roles/schools, scheduled access,
private signed exports, corruption/revocation/expiry, scratch cleanup and retention.
Panel Playwright covers both widths, wrong-role refusal, axe and overflow. Staging,
actual retention-policy acceptance and recipient hand-off remain external evidence.
