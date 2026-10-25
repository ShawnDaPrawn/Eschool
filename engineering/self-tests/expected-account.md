# Expected account binding

Server check is optional and compares the authenticated account before school
selection, data reads and domain writes/replay. It adds no authorization grant.
Activity/message repositories send their pinned account through ApiClient.send.
Older clients retain existing behavior. The combined contract/SDK adds optional
ExpectedAccount headers; cover configuration also accepts the omitted school year
using the owner's current-year API implementation and regression.

Actual checks so far: cookie/bearer/malformed-header suite 3 tests / 16 assertions;
shared API client tests 28 passed; updated activity/message repository tests 14
passed; SDK 58 tests passed including optional-year/header transport. The new role
non-grant assertion and broader contract cohort are recorded below after execution.
Strict configured OpenAPI lint passed; full Larastan passed; generated SDK analysis
has no errors/warnings and 504 existing style infos. A semantic comparison proves
all prior schemas and paths unchanged except optional account headers and the one
optional cover query. No physical-device or production acceptance is inferred.

Final server/contract cohort: 256 tests / 1,367 assertions passed, including the
owner's default-current-year cover regression. Final focused account suite:
3 tests / 17 assertions passed after adding an explicit matching-header wrong-role
refusal. Core analysis has no errors/warnings and one existing idempotency-header
style info. PHP formatting and whitespace checks passed. The owner's cover
controller/request/default-year regression are included so the optional SDK query
matches actual server behavior in this shared branch.
