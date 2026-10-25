# Private live transport

Implementation: opt-in Laravel Reverb service, immediate outer-commit outbox
acceleration and a dedicated database/live relay. Four typed v2 operations issue
or renew a credential-bound lease, authorize a channel, and join/leave count-only
group presence. Physical channels identify one school, account and credential
lease; retaining one revoked device cannot receive another device's next fanout.
Token deletion/expiry, password changes, active roles, school status, modules and
source authority are rechecked before transport. Platform and impersonated actors
are refused. Production readiness also requires a shared database/Redis cache.

Permitted events contain bounded references only: house aggregates, private own
recognition references, public-live consultation aggregate invalidations and
current activity changes. Clinical events, message bodies, names, answers and
anonymous participation references are excluded. Group presence counts distinct
currently authorized users, not devices; subscription alone is not a presence
heartbeat. Presence expires after 45 seconds without renewal and has no history.

The core client uses actual native/browser websocket transports, strict metadata
parsing, duplicate revisions, credential/context guards and lifecycle shutdown.
A fresh MeContext generation replaces its session even when loading/loaded are
coalesced into one frame. Legacy clients do not probe live endpoints unless the
explicit module is present. Dedicated profiles require the fresh live_updates
capability. Reconnection emits a refresh signal so consumers fetch protected
HTTP readers rather than trusting socket content.

Evidence:

- Seven real MySQL HTTP/credential/source/presence cases: 135 assertions. These
  exercise individual device revocation, account/school channel binding, private
  refusals, expiry, explicit distinct-user presence, current assignment withdrawal
  and real outer commit/rollback behavior.
- Affected groups plus live cohort: 41 tests / 740 assertions passed after adding
  transactional activity invalidations. Broad route/contract cohort: 436 tests /
  2,023 assertions passed; existing paths/schemas are preserved.
- Actual Reverb process + real database worker + RFC6455 client passed: committed
  poll invalidation arrived in 0.271 seconds, then 0.304 seconds with explicit
  forged client-event refusal (4301). Only server-authorized generic metadata
  arrived. Processes are stopped after each test. This is a local transport
  benchmark, not production/load/mobile evidence.
- Five core tests use actual IO sockets: protocol handshake, scope headers,
  deduplication, role/token withdrawal, background reconnect and late HTTP refusal.
  The MeContext widget regression verifies explicit module opt-in and identical
  context refresh replacement. Narrow core/session/family analysis has no issues;
  Teacher retains two existing style infos and no errors/warnings.
- Pinned generation produced 1,752 SDK files; all 110 SDK tests passed. SDK
  analysis has zero errors/warnings (preexisting generated style infos remain).
  Strict contract lint, whole-app Larastan and Pint passed.

The subsequent visible-consumer evidence is recorded in live-consumers.md;
Sanctum lifetime review evidence is in live-token-lifetime.md. Home recognition
moments/counters, published CI and deployed/device/load acceptance remain
separate. This source does not claim all Phase 4 or production scaling complete.
