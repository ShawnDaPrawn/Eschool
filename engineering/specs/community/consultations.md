# Polls, surveys and nonstatutory elections

Build a school-owned consultation workflow in the existing communication area.
Use the current message audience definitions and current recipient-authority
checks, including dated owned activity groups and fresh guardian/enrolment scope.
A facilitator can reach only their own authorized groups. A query flag or saved
audience never grants access. The consultations module remains independently
configurable and read-only preserves authorized reads while refusing new votes
and configuration changes.

A draft contains the type, title, plain-language instructions, year, audience,
opening and closing instants, anonymity and results visibility. Questions support
single choice, bounded multiple choice and text, with stable question/option IDs.
Election candidate statements are scoped to the school; this workflow does not
claim statutory governing-body election compliance. A reviewed configuration is
frozen once published, except cancellation/closure with a reason. Configuration
changes have revision checks, immutable operation receipts and an audit trail.

One participation row per school/consultation/account is enforced by a unique
index. Anonymous participation records only eligibility/participation; responses
use unrelated random UUIDs and contain no actor, operation UUID, response hash,
participation reference or identifying timestamp. Never put anonymous answers in
actor-linked audit, operation receipts, notification payloads or generic events.
A repeated anonymous submission is refused as already recorded; a separate
participation read confirms the outcome without replacing answers or returning
a response identifier. Named responses are explicitly labelled and
available only under the configured authority. Every question and option is
validated against the frozen consultation, with bounded answers and no client
supplied author/tenant fields.

Scheduled opening uses a school-context worker with transactional outbox and
idempotent generic notices; user preferences and school communication rules
control in-app/email delivery. Admission to vote and results reads rechecks fresh
source authority. Private results stay hidden until close, including manager
previews that could otherwise expose a private running tally. Public running
counts are aggregate-only and later use the authorized live hub. Results expose
counts/denominators and explicit unavailable/closed states, with no anonymous
participant/answer pairing. Cancellation is retained and cannot silently reopen
an already-used ballot.

Implement domain/migrations/tests first, then additive typed API and generated
SDK, panel authoring/voting/results, and shared staff/family readers. Validate
wrong school/role/owner/option, duplicate/concurrent vote, closed/open boundaries,
immutable configuration, anonymous physical separation, permission withdrawal,
readonly, queued delivery withdrawal, migration retention and real profile flows.
Live email/provider, deployed scheduling latency, statutory legal sign-off and
external acceptance remain separate evidence; they do not freeze independent
implementation.

The additive API uses account binding and an early private-response boundary on
success and refusals. Named audience pickers include current selected choices and
paginate independently. The manager configuration history is paged separately
from an encrypted event snapshot, avoiding large snapshot bodies in list reads.
Aggregate results never include text answers. Only authorized managers can page
individual closed-survey answers; anonymous entries have explicit null identity
and no returned response UUID or timestamp. Clients get independent read/write
capability metadata for read-only modules and historical records.

Staff inclusion is explicit. It adds current school staff to a school audience
including one aimed at guardians, current coaches to owned activity-group
cohorts, or staff explicitly selected by a saved list. Grade, homeroom and
academic-group ballots retain their learner/guardian electorate: the editor
hides staff inclusion there and the server refuses an unsupported true flag.
A parent-audience staff opt-in never adds the parallel learner electorate.
