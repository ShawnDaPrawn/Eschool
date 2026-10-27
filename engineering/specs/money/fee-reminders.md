# Scheduled private fee reminders

Phase5 increment2, authorized through increment15. Finance configures an enabled
flag, minimum overdue calendar days, minimum days between prompts and earliest
school-local sending hour. No schedule is active until saved explicitly. An hourly
`fees:remind` command isolates schools and skips unavailable recipients. An actual
failure is logged by school/exception class and returns failure for recovery.
School and policy locks serialize competing runs. Each account/parent/school date
has one durable intent; retries and ordinary notification retention preserve cadence.

Eligibility uses the same current parent-role, guardian fee-visibility and own
invoice-holder-share projection as the private parent statement. An overdue share
must remain outstanding after effective allocations and credit notes. Other holders'
debt, held/pinned credits, future dues and hidden children do not widen eligibility.
Delivery rechecks access, enabled writable module, policy and current overdue shares.
Clearing debt or revoking access suppresses queued transport. No ledger posting is
created. Disabling reminders is independent of learning and statement access.

Notifier receives a generic title/body and only the private `/fee-statements` link
plus an internal reminder reference. No amount, learner name or other holder detail
enters email/push or delivery payload. School channel rules and parent preferences
remain authoritative; email defaults off. The existing context-bound app deep link
opens the fee statement only after the app's current account/school checks.

Private inbox SQL limits prompts to their recipient, school, current guardian/holder
scope, enabled policy and expiry. A generic historical prompt may remain until its
expiry after payment; opening it always loads the current authorized statement.
No notification promises the displayed historic balance is still owed. Finance's
private panel shows intent history and explicitly distinguishes queued from delivered.

Immutable intent metadata retains cadence and audit. A notification can be bound
only once, with school/type verified by a database trigger. Ordinary notification
retention may null the scalar notification FK without erasing cadence or permitting
rebinding; the account uses a composite school FK. Populated rollback refuses.
Finance configuration uses revision and permanent actor-bound UUID receipts. Forms
bind actor/school, CSRF and private errors without flashing financial form contents.

Tests cover schedule defaults/calendar/hour/cadence boundaries, split-family scope,
preferences, revoke/credit/disable suppression, real foreign-school references,
transaction rollback/retry, private role/binding and retained history. Browser
journeys cover Finance and refused Teacher at390/820/1280px. External delivery,
fluent language review and staging/device evidence remain separate release records.
