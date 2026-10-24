# Communication centre: audiences and messages

Implement Phase 2 audiences, role approval, scheduled notification delivery,
read state, the panel workbench and shared adaptive two-pane messages. Retain
legacy announcements/chat and their v1 contracts. Polls, surveys and elections
remain Phase 4.

Standard audiences are school staff/learners, a grade, homeroom or teaching group,
and parents/guardians of the learners in any of those scopes. Use dated physical
enrolments and group membership; custom lists contain explicit current school
contacts and are saved with a revision. Ordinary teachers address allocated
classes/groups and their guardians. School-wide addressing requires an explicit
role rule or current school leadership/management authority. Custom lists cannot
bypass those source-contact boundaries. School management controls the rules;
ordinary teachers default to approval, while current grade heads need none.
Draft preparation does not wait for approval. Current principals/managers default
to no approval; explicit role overrides apply, except the grade-head exemption.

Each edit uses revisions and operation UUIDs; uncertain retries retain the exact
body/UUID. Editing a pending/rejected draft clears prior approval. Approval is by
a different currently authorized leader covering the audience. A preview shows
the current target/count, channels and school-local schedule. A submitted future
audience is resolved at delivery time, allowing normal roster changes. Fresh
sender/rule/approver checks precede publication. Published bodies, scopes and
channels are immutable; withdrawal hides readers and stops pending transports.

Messages and custom contact lists are encrypted at rest. Generic notifications
carry a private app path and IDs, never the private subject/body. Notify through
the existing Notifier with the selected channels, school rules and user choices.
A durable per-school queue cursor, per-recipient processing and transport claims
make retries resumable and deduplicated. A scheduler recovers pending work.

Publication freezes eligible recipients and their access reasons. A reader must
be one of them and still have an active school account; guardian links, learner
access/approval and staff roles are checked again. Grade/group changes do not
invent new historical recipients. Ended guardian rights and withdrawn messages
cannot be read or delivered. No recipient lists or private read statistics go to
family readers. Read acknowledgements are idempotent.

Tests cover audience resolution, dated/ended relationships, role/owner/school
boundaries, approvals, schedules, role changes, replay, queue/fanout/transport
races, preferences, read state, shared cubits/widgets and browser flows at both
panel widths. Record staging/device and pilot acceptance separately. No actual
messages to real people or production release are part of this implementation.
