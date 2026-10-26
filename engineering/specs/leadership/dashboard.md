# Principal grade trends

The dashboard requires a fresh current principal appointment or a school Admin/
Super Admin with academic-management permission, an active school membership,
completed password setup, and the leadership_dashboard module. Platform operators,
including impersonation, cannot open these aggregates. Current principals can inspect
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
collection reads the existing school fee receipts through LeadershipFeeCollections.
Only settled, non-deleted transactions with exact integer-cent totals are counted;
their sum must match exactly one running FeesPaid balance for the same school,
learner, class and year. The running balance is a reconciliation check and is never
added to those transactions. Missing cents, duplicate/inconsistent balances,
unknown receipt kinds, ambiguous dated enrolments and unsafe totals are unavailable.

Receipts are assigned to one matching dated enrolment and class, using their
recorded calendar receipt date. An older optional/installment transaction without
a date may use its exact matching settled components only when they agree on one
date and their cents reconcile. New office optional receipts retain their form
date on the transaction. No created-at guess or float fallback is used. A known
empty source is zero; a disabled fee source is unavailable. Retained read-only
fee modules remain readable, and principal and module authority are rechecked.

The view explicitly describes current recorded receipts after recorded corrections.
Legacy removals adjust the original receipt amount/date; this projection is not
a bank balance or a refund-date ledger, and changes no payment postings. Phase 5
financial products remain separate. Missing metrics never appear as zero. No clinical needs,
counselling notes, names, incident bodies or per-learner risk labels are returned.
