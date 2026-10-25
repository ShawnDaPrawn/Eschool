# Principal grade trends

The dashboard requires a fresh current principal appointment, an active school
membership, completed password setup, and the leadership_dashboard module.
School administrators without the appointment and platform operators, including
impersonation, cannot open these aggregates. Current principals can inspect
retained school years; input periods are bounded to twelve weeks, the selected
school year, and today. Access is checked again after aggregation.

Attendance counts recorded physical school days, includes half-day attendance,
excludes holidays and lesson registers, and requires a matching dated enrolment.
Canonical attendance replaces the legacy mirror for a learner/day. Rates use
integer basis points. Enrolment counts use the period end. No unrecorded day is
invented as either present or absent.

Daily activity receipts contain only school, learner, dated grade, year and date.
Only successful authenticated API use by an approved, active enrolled learner
is observed. Duplicate requests do not create duplicate days. Cross-school token
last-used times are deliberately excluded. Activity before the first observation
is unavailable; a period spanning the first observation is partial. A daily
command deletes observations older than ninety days. Canonical academic history
is not removed by that command.

LeadershipSignals is the aggregate integration boundary for distinct learners
requiring academic follow-up and learners with discipline incidents. The risk and
discipline feature services populate it; unavailable sources return null. Fee
collection remains explicitly unavailable until its reconciled financial source
is available in Phase 5. Missing metrics never appear as zero. No clinical needs,
counselling notes, names, incident bodies or per-learner risk labels are returned.
