# Rule-aware progress-band alerts

Phase 3 progress alerts reuse the canonical running mark and school thresholds.
Current staff evidence and family released evidence have independent state.
An initial sufficiently assessed band counts as entering it. Missing evidence
becomes insufficient, not zero. An unchanged band does not repeatedly notify;
a transition out and back creates a new revision. School band-setting changes
record a transactional outbox event and queue rechecks. Family processing ignores
older release entries, including an old event arriving after a newer release.

School-owned state and immutable alert revisions use a school row lock. Every
alert retains its exact canonical snapshot and optional release or teaching group.
The notification claim survives retries; recipient/channel dispatch remains the
existing Notifier responsibility. Marks, assignment, assessment, module and
threshold events queue bounded school-specific jobs. No model/provider is called.

`progress.band_changed` is offered to learner/parent; `progress.staff_band_changed`
is offered to teacher/admin, under academics. Standard optional preferences apply:
push/in-app default on and email default off. These types carry only a generic
update message and opaque alert ID, with no learner name/ID, subject, score,
attendance, risk, clinical text or labels. Family notices open Learning progress;
staff notices open the private note/progress workbench. Staff mail uses the panel
host. Clinical notes are not queried by this implementation.

Fanout, Notifier and final transport independently resolve fresh source and actor
scope. Family requires own guardian/learner authority and current approval plus
frozen released evidence; current raw mark edits never replace it. Staff requires
current allocation/leadership, membership and the current snapshot. Later band,
source, setting, role, allocation, guardian, approval, module, preference or
destination withdrawal suppresses the stale delivery. Push stringification of
numeric opaque IDs is accepted without accepting booleans or malformed IDs.

Inbox history contains the same non-personal generic message; private values are
retrieved through the separately authorized progress readers. Retained alert/state
rows block destructive migration rollback. School-owned table registration and
the actual MySQL migration rehearsal are covered. No real mail, push, learner,
provider, production or fluent-language acceptance is claimed by synthetic tests.
