# Source-aware discipline updates

Each current discipline revision can request a generic notification for its
eligible grade leadership, appointed principal or published family audience.
Pending escalation and principal hearing notices stay in staff purposes. Family
updates require a currently published record and fresh own-child authority.
Detention assignment/status updates use their own immutable source revision.
No learner name, infraction, reason, description, clinical label, count or record
identifier enters the external message body. Its opaque intent identifies a
server-side school/recipient/source receipt, never an authorization grant.

An outbox listener reads IDs only, ignores obsolete events, and creates one
receipt per recipient and source revision. Replays reuse that receipt. Fanout
starts after commit, including a synchronous queue configuration. Source and
recipient are reauthorized immediately before transport; old revisions,
withdrawn appointments, guardians, approvals, allocations, module access,
platform identities and impersonation fail closed.

Register the literal `discipline.` prefix with the common private scope/delivery
registry. Its SQL joins recipient, school and live source ownership before
pagination, unread counts, show or mark-read. Teacher and dedicated staff inbox
routes open the existing school discipline workflow. Families open only the
existing published own-child history. A notification confers no hearing,
expulsion, counselling, private incident or support-plan permission.

The backend implements the five literal family/staff/review/hearing/detention
types, an append-only recipient/source receipt table and the record/detention
outbox listeners. Each notification contains only its generic destination and
opaque intent UUID. Current canonical discipline policies decide access before
notification bodies are selected; the common execution-time scope also refreshes
prepared queries. Notification counts, read markers and transport use those same
sources. Dedicated-profile navigation and combined app browser qualification
follow in the client increment.
