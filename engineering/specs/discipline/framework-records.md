# School discipline, review and detention

The discipline module is disabled by default. A school administrator with current
academic-management permission or dated principal configures a year-specific
framework before staff can record entries. No punitive defaults or expulsion
rules are invented: the framework explicitly defines ordered levels, named
infractions, approved sanctions, occurrence thresholds and a reset rule. Top
levels refer to the principal for a hearing and never expel automatically.
Framework revisions are retained with each entry; edits do not rewrite history.

Recording requires fresh school membership, the discipline module and either a
current teaching allocation for that learner or a dated grade-head/principal
appointment. The original grade, reporting year and occurred-on date are pinned.
Repeated confirmed occurrences within the configured reset window suggest the
configured next level. Escalations await grade-head review; a hearing requires a
separate principal decision. Every confirmation, reasoned overturn, hearing
resolution, parent acknowledgement and detention attendance change is audited.
An overturned entry remains retained and is excluded from subsequent escalation
and aggregate counts. Staff cannot rewrite the original learner, framework,
infraction, occurrence date or grade after recording.

Learners and guardians see only their own published discipline history through a
separate history surface, with fresh ownership/guardian/approval checks. Draft or
pending-review internal reasons never enter family reads. Acknowledgement is a
verified current guardian action, not agreement or waiver, and cannot change the
sanction. Platform operators, impersonation and a Counsellor role alone confer no
discipline access. Clinical reports and counselling text are never imported.
School-year histories remain distinct from the permanent learner profile.

Detention sessions have a dated assigned supervisor and explicit learner slots.
Only approved detention sanctions can be rostered; each learner remains in the
same school/year/grade scope. Missing attendance remains unknown. Parent/learner
history shows the owned attendance only. Grade heads can correct a recorded
attendance state with a reason; every change retains an immutable version.

Leadership receives only distinct learner counts per reporting grade/year/date,
with no infraction, reason, sanction body or learner identifiers in aggregates.
The early-warning behaviour adapter consumes authoritative confirmed discipline
counts only when its module is enabled. Confidential wellbeing stays unavailable.
Domain tests precede typed API, panel and app increments; no placeholder screens
or unrun month-long school acceptance are represented as completed behavior.

## Private contract and receipt boundary

The typed HTTP increment exposes school configuration/framework publication,
bounded staff learner/record/supervisor choices, original record detail/history,
reasoned grade review and principal hearing, family-only published history and
revision acknowledgement, and supervised detention scheduling/roster/capture/
cancellation. Every private write has a stable operation UUID and an encrypted
feature-owned receipt with a keyed intent hash. Replays recheck fresh school,
role, allocation/leadership, guardian or explicit supervisor authority. They never
use the global plaintext API replay cache. Writes reject query parameters before
merged validation; body and response caches are forbidden. Acknowledgement never
grants staff access or acknowledges a different guardian's current revision.
