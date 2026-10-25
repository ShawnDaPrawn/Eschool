# Learner and parent incident reporting — 25 October 2026

The real learner/parent home card, Settings entry and deep route provide two
explicit paths. Anonymous opens the separately verified stateless public form
without an account, child, code, query or fragment in its URL. Named reporting
shows the signed-in name, requires explicit identification consent, and links
the selected own learner only when the reporter explicitly chooses that option.
Previously anonymous reports have their own separate permanent-identification
consent. No emergency-guidance acknowledgment blocks reporting.

Private follow-up codes travel only in POST bodies. Named reads also require
the original current reporter and fresh guardian/learner authority. Bodies,
codes and unsent drafts stay in memory; backgrounding and every authorization
generation refresh erase them. Unknown submissions retry the original secret
and payload; replies retry their original operation UUID. Private transport
suppresses URLs/bodies from debug logs. Code/draft storage is not added to Hive.

A real server regression was first RED: read-only mode incorrectly treated the
POST follow-up read as a mutation. Route/action guards now mark that operation
as read while all true writes keep explicit module-write and domain guards.
The named owner can read, replies remain refused and another reporter remains
forbidden. The complete actual wellbeing HTTP cohort passes 10 tests / 518
assertions; changed PHP Pint and full Larastan pass.

Actual verification:

- Clinical, reporter and transfer shared cohort: 56 tests pass, including
  all 16 new reporting tests (390/820/1280, consent, separate identification,
  anonymous URI, immutable retry, read-only read, private cookie transport,
  late responses and same-account role/module/approval refresh).
- Complete learner/parent app suite: 56 tests pass. Shared/family analyses
  have zero errors/warnings; existing and brace/style infos remain reported.
- Actual Chromium: both learner and Parent startup/login, real home card,
  explicit consent, submit/read/reply, account/school pins, no body/code in
  auth/settings boxes, and denied-resume erasure pass. The first harness
  attempt omitted profile selection; the corrected full two-profile run passed.
- Parent transfers #147 remain integrated, including its real card/routes,
  complete 52-label catalogue and SA glossary corrections. Offered app locale
  key sets remain identical to English, with explicit fallback pending review.

Photos and generic fresh-authority notices are separate follow-on increments.
No physical-device, ingress/body-log, live clinical response, fluent language,
staging or production qualification is inferred from these synthetic tests.

Final combined real wellbeing HTTP and full canonical route catalogue:
354 tests / 2,141 assertions passed. No API schema or SDK regeneration is
required for the POST-read purpose correction or these app screens.
