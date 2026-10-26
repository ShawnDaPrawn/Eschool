# Authorized live invalidations

Status: local transport and shared socket-session implementation verified; see
engineering/self-tests/live-updates.md for actual evidence and pending consumers.
The transactional outbox remains the recovery source. Laravel Reverb stays
opt-in, without provisioning a service or enabling production defaults.

Clients receive bounded invalidation references, then fetch the existing
protected API reader before displaying a counter, personal moment or other
content. Socket payloads contain no names, prose, marks, case/report fields,
message bodies, consultation answers or anonymous participation information.
A revision makes duplicate delivery safe; delivery order cannot grant access.

Physical transport channels are private and bound to one account and school.
Logical school/group subscriptions additionally use fresh school membership,
module and dated group/guardian authority at publication. Re-resolve recipients
for every event: retaining a websocket after role, guardian, enrolment or group
withdrawal must not keep an account in the next fanout. Personal recognition
references never use a shared school or group channel. Platform or impersonated
sessions cannot use a live channel to reach clinical data; clinical payloads are
outside the transport's permitted event catalogue entirely.

The server catalogue initially includes school aggregate `house_points.changed`
(school, school year, house, revision) and person-only `recognition.changed`
(kind, source reference, revision). It uses the existing RecognitionAudience
source policy and resolves the source record in its actual school. The root
recognition clients map both to their existing invalidation stream and refetch
fresh counters/profile/history; a personal Home consumer displays only an
unseen authorized moment. Later group/session invalidations use the same
metadata contract with their own explicit audience resolver.

Schedule a narrow outbox accelerator after the *outer* database commit,
independent of the application default queue. Live jobs use the existing database
queue on the domain database. Its insertion commits atomically with the outbox
claim; the external publish runs in the separate worker outside a transaction. A rollback must produce neither socket
publication nor a surviving relay job. The minute outbox drain recovers failed
relay work and is not the normal latency mechanism. Never perform an external
publish while a caller's domain transaction remains open. Transport failures
must not roll back an already committed school action; recovery remains queued.
A dedicated `live` worker handles these jobs immediately; the minute scheduler
recovers a failed initial enqueue. A sync driver or a separate queue database is
not treated as the same atomic transport guarantee.

Shared client sessions pin account, school and bearer identity. Loading a fresh
MeContext ends the old generation even when the resulting payload is identical.
School/account change, sign-out, disabled module and backgrounding close the
socket and erase queued events. Reconnection authenticates again and refetches
current authorized readers; periodic HTTP refresh is an explicit fallback.
Neither an event's school/source identifiers nor a caller's subscription request
is authority to fetch the referenced content.

Verification will exercise wrong school/account/role, dated role and guardian
withdrawal after connection, stale token/session replacement, private person
versus school fanout, out-of-order/duplicate revisions, outer commit/rollback,
reconnect and foreground refresh. A real local Reverb websocket round trip will
measure commit-to-consumer latency separately from the deployed target of two
seconds; no local benchmark represents a production, mobile-device or network
qualification. Presence membership, if exposed, is limited to separately
permitted group users and excludes sensitive information.
