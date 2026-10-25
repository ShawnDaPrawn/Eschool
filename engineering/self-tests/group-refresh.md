# Group authority refresh — 25 October 2026

Both installed app profiles recreate activity readers whenever account authority
loads, including Loading/Loaded events that coalesce before another frame and
return identical account, school and role fields. A captured generation also keeps
old repositories inactive after the replacement reader starts. Current roles,
guardian/approval scope, module state and read-only state are compared before
private content is shown. The shared facilitator shell already owns an equivalent
session epoch and is unchanged by this patch.

Actual evidence:

- The actual teacher route test failed against its original route: the cancelled
  private group remained visible after an identical context refresh. The patched
  route passes and fetches the server group list again.
- Shared activity boundary/repository/widget cohort: 29 tests passed, including
  coalesced-refresh draft removal, old-generation denial before transport, pending
  refresh hiding and role/module/guardian/read-only changes.
- Actual teacher route plus existing routes: 23 passed; actual parent route plus
  existing routes: 34 passed. Both use the real app route and API repository.
- Shared focused analyzer: no issues. Both app focused analyzers have zero
  errors/warnings; existing style infos remain (3 teacher / 8 family).

The prior feature-level transient network behavior retains in-memory drafts only
while account authority remains current. An account-authority refresh destroys
that reader and its draft; no durable private offline cache is promised. No new
API/SDK or native build configuration changes. Chrome/device/store qualification
is not inferred from these widget and route tests.
