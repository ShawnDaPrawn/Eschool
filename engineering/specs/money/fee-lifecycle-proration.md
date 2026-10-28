# Explicit enrolment proration

Finance selects a current-year policy for mid-year joins, leavers and installment
handling. Join choices are daily, calendar-month or full annual charging; leaver
choices are daily, calendar-month or no credit. Dates are inclusive. Calendar-month
charging includes each touched calendar month. Finance explicitly chooses whether
to retain contractual due dates or redistribute over remaining dates, using the
reviewed due date if all configured installments are past. No rule is guessed.

The shared fee calculator uses canonical confirmed dated enrolment. Daily and
calendar-month proportions use exact integer units and one final half-up rounding,
including leap years and years crossing January. The bill review retains original
annual rate, actual enrolment, policy revision, charged period and exact reduced
amount. Installments and original-holder cents sum to that amount. Posting rechecks
the complete review and preserves existing immutable invoice SQL guards. Old posted
history is unchanged. Missing policy for a partial enrolment remains an explicit
exception while full-year billing can continue.

Later leaver adjustments use the same policy against original billed periods and
prior credits; refunds and payer corrections are separate reviewed features in
increment6. This slice does not claim those later operations are implemented.

Continuous school enrolment spans adjacent dated homeroom segments. Class moves
never create a false join/departure or a reduced annual charge. Original bill rows
retain all segment identities/dates. Real gaps, overlapping segments and unconfirmed
imports remain explicit exceptions requiring review instead of inferred school exits.
