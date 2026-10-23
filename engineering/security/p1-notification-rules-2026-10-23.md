# Notification rules security verification

School rules use scoped tables and office authorization; preference writes use
only the authenticated human. Tests refuse foreign school selection, wrong
roles, unavailable types/channels and mandatory overrides. Current membership
roles are resolved per school, even if a User object retains roles loaded under
another school.

New notices require explicit recipient links, preventing inbox opt-outs from
being bypassed by broadcasts. Historical broadcasts retain their existing
availability without promoting old private assignment notices to all learners.
All legacy inbox producers now use the notifier; content uses individual device
tokens rather than audience topics. Silent context invalidation remains on
school topics and contains no personal data.

Encrypted mail/push jobs recheck mutable membership, destination, module and
preferences before transport. Handle notices also require a pending unexpired
proposal and current verified approval rights. Unverified email gets generic
content. Transport work runs under the recorded delivery school. Database queue
rows, outbox fanout and recipient claims share the transaction on the default
database queue. Rollback and replay tests passed. Exact stale FCM tokens are
cleared on UNREGISTERED; replacement tokens remain intact.

Notification delivery metadata is hidden from v1. API links are local app paths;
external origins, backslashes and newline injection are refused. Mail failure
metadata excludes private bodies, recipient addresses and credential data.
Notification images are validated and stored under their school's prefix.

This is local automated evidence. SES/FCM configuration, real-device delivery,
operator review and production acceptance remain pending. The existing guardian
approval rollout flag is unchanged. Separate invitation lifecycle review remains
part of the Phase 1 identity follow-up.
