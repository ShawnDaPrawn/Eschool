# Relief-cover panel

The existing Material 3 panel now supports named teacher requests, mapped dated
occurrences, available substitute choices, assignment, explicit refresh/cancel,
private reasons, personal assigned lessons and paginated request history. Fresh
service authority also controls navigation for teachers and explicit managers,
including current principals using custom school roles.

Private validation/conflict pages preserve the submitted text in the response
without old-input session flash. The current record remains visible for explicit
comparison before retrying. Actual record years and the selected header year both
prevent historical writes. A shared private-form registry handles early year
refusals for relief cover and activity materials; bounded, escaped submitted text
is returned with private/no-store headers and never stored in session flash.
Consumers must register additional private form paths as they are introduced.

Automated panel coverage exercises request/assignment/cancel, named choices,
owner/role/school/module refusals, malformed inputs, private draft recovery and
both forged-body and selected-header year refusals. The affected panel/API
cohort passed 11 tests with 163 assertions before the final registry extraction. The final panel/API/locale cohort then passed
15 tests with 22,264 assertions, including the selected-header privacy check. Full
configured Larastan and Pint pass. Afrikaans/isiZulu catalogue completeness and
placeholders pass; fluent review remains external acceptance.

Actual Playwright browser tests passed at 1024 and 1280 pixels: manager request,
named substitute assignment, substitute-only dated view without private reasons,
and cancellation. Serious/critical axe, unique control IDs, horizontal overflow,
own-origin console and network checks passed. The synthetic browser runtime
used the local test database, array mail and a database queue; no real provider
or school records were used.

Shared Flutter workbenches and expected-account binding are separate following
increments. No whole-phase, classroom/device, staging or production acceptance
is claimed by these checks.
