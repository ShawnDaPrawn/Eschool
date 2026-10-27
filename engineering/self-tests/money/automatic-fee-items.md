# Automatic fee item qualification

Compulsory/membership rules and shared one-off/batch eligibility are implemented.
The first expanded targeted run passed13 tests/93 assertions: family deduplication,
explicit payer exceptions, dated activity/route and teaching/subject membership,
phase filters, changed price/rules/membership/guardian rights, original VAT posting,
real foreign item/batch/source records, private binding/validation, replay, SQL
immutability, disabled history and rollback of all child invoices/receipts after a
late failure. Initial development tests caught use of school_id on the platform
reference grade table; the trigger and fixture now use canonical global grades.

First Finance/Teacher browser run passed4 cases, zero retries at390/820/1280px:
configure a route source, review two siblings as one family charge, post/replay,
verify ledger totals, revoke membership and show no new eligible charge, accessibility,
no-store, overflow, duplicate IDs, console/HTTP and Teacher denial. The SQL batch
identity guard was subsequently strengthened and the complete final run is tracked
on the feature PR; do not count unexecuted follow-up checks as passed.

Empty and pre-automatic posted manual invoice/charge up/down/up rehearsals passed
without changing2 invoices or2 ledger entries. Static analysis initially identified
an erased generic builder model type; accurate generic method contracts preserve
the queried models. Final baseline-free Money5, configured panel, fresh-cache Pint,
TypeScript/flows discovery, Money regression and docs results belong on the PR.

Parent opt-ins are the next part of increment4; they are not implied by a membership
or a compulsory rule. No new API contract or app protocol is added by this bounded
Finance feature. Gateways, live activation, fluent translations, staging/devices and
independent money security review remain separately tracked release evidence.

Final Money regression passed198 tests/1,539 assertions, including the private VAT
validation follow-up inherited from PR318. Final strengthened-guard Finance/Teacher
browser rerun passed all4 cases, zero retries. Baseline-free Money5 and configured
panel static analysis, fresh-cache Pint, TypeScript, actual CI flows discovery of4
cases and documentation build passed. Tenant/year checks and exact hosted source
qualification/integration are tracked on the feature PR.
