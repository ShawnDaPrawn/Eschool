# Capturing and viewing academic marks

Deploy the marks migrations and API before the updated apps. The server advertises
`academic_marks` in the current school context; an older server keeps the entry
hidden. Teachers open Assessment marks from Home or Settings, choose their dated
teaching group/year and term, then select a published task. Parents and learners
use Assessment marks in Settings to view released periods only.

On a laptop, Tab moves between question columns and Enter moves to the same
column for the next learner, including rows outside the visible viewport. Paste
rectangular tab-separated values from Excel into the selected cell or use Paste
from clipboard. Decimal commas are accepted as input and sent as exact decimal
strings. Invalid, oversized or partly read-only pastes change no draft values.
On a phone, the same capture uses learner cards. Each learner's details action
sets missing, absent, excused or catch-up with a reason.

Running results come from the server after a short debounce. Save marks commits
original row revisions atomically. A conflict preserves your entries and disables
blind retry. Compare with saved marks, then explicitly use the latest saved
values and re-enter the intended correction. Network failures retain in-memory
drafts; leaving/reloading asks before replacing them. Drafts do not survive an
app restart. School/child switches clear private academic screens immediately.

The server determines capture, correction, moderation and release capabilities;
Root Admin does not gain a teacher's capture rights. A moderated correction
needs grade-head authority and a reason. Locked terms remain read-only. Select
learners to moderate or release, preview release first, review incomplete results,
and choose official or explicitly provisional mode. A changed preview requires a
new preview. No fee check limits release or access.

Family screens show only freshly authorized own-child/own-learner releases,
including period, subject, grade, percentage, coverage, rating and provisional
state. They never request staff matrices or display drafts. Existing exam/result
phone screens remain available under their existing contracts.

Automated browser fixtures exercise the actual app entry points against fake
HTTP responses. They do not certify pilot sheets, classroom timing, staging
performance, physical devices, independent review or production acceptance.
