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

## Server-authorized session attendance correction

The register now requests its selected session explicitly. The server derives
eligible learners using the same dated activity membership, school year and
enrolment query as capture, then includes retained captured learners for
corrections after withdrawal. Route group/session identity and dated staff
allocation are checked before names or attendance are returned. Both shared apps
and the panel consume that projection; the apps refuse a missing or mismatched
projection instead of guessing eligibility against an older server.

The existing unselected roster response is unchanged. The optional session query
and optional projection are additive, with the pinned SDK regenerated from the
combined notes/revocation contract. Null unrecorded status and exact retained
revisions round-trip without loss. Registers use the fresh session revision.

The backend and actual widget regressions both failed before the correction.
The complete group/API-contract/controller cohort passes 316 tests / 1,955
assertions. Shared repository/widget/session tests pass 33 tests, including a
withdrawn learner excluded while valid classmates remain selectable, and a
retained learner with no membership row still available for correction. All 70
SDK tests pass. Strict OpenAPI lint passes. Final static results are recorded
with the published revision; native/device qualification remains separate.

Final merged checks: full Larastan has zero errors; the inherited shared clinical
password boundary passes its complete 11-test / 35-assertion cohort. Pint and
whitespace checks pass. Shared analyzer has zero errors/warnings and three
existing message-widget style infos. An unrelated unused Cover test import was
removed after the full shared analyzer exposed it. No native build was run for
this Dart/server correction.
