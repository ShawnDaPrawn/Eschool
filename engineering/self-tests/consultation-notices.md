# Consultation opening notices

Opening notices use the existing scheduled outbox event, with generic wording
and consultation/configuration revision references only. No response or
participation data is read or included. Family and staff intents use their
implemented portal paths and existing optional channel rules; source-key
uniqueness and channel claims preserve retries.

Registered source and delivery guards reuse the current consultation voter
policy. They recheck school, roles, guardian/enrolment and owned-group authority,
module, year and opening/closing state. SQL inbox filtering precedes counts,
pagination and body selection. PrivateNoticeScopes additionally re-resolves the
provider on each Eloquent query execution, including prepared query reuse.

Evidence: final 11 MySQL notice tests / 195 assertions passed, including
scheduler once-only opening, generic metadata, guardian/role/prepared-query
withdrawal, wrong school/revision/link, opt-out/idempotent channel claims,
real queued-email suppression, facilitator owned-group inbox withdrawal and
outer rollback. Whole-app Larastan512M and Pint passed. The preceding cohort
also passed all 10 shared scope, 8 staff inbox and 5 electorate cases.

An additional real electorate regression confirms parent-audience staff opt-in
adds staff and guardians without adding unrelated learner votes. The initial
notice cohort exposed this gap. Two fixture errors (required module setup and
passing the real fanout link), followed by a delivery-row lookup correction,
were corrected without weakening a delivery check.

Existing learner/parent/Teacher notification cards now offer a typed action to
the current account's protected consultation list; pushes and email retain the
specific ballot link. This avoids exposing raw payloads through the v1 API.
Actual full-app Chrome now passes the notification-card action for classic
Teacher, Student and Parent. Dedicated Facilitator opens the same reader through
the registered private inbox. Home-card, vote and withdrawal flows are recorded
in consultations-apps.md.
No live email provider, deployed schedule latency or external device acceptance
is claimed.
