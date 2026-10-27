# Fee plans and enrolment preview

First Phase 5 billing increment, after the immutable ledger preparation. Finance
staff with `fee-plans-manage` can create, revise and archive annual plans, matching
all learners, one canonical grade or one school class. Amounts are integer cents
with explicit currency. Draft/archived plans never match. A new-year copy takes
an explicit uplift (0–100%, at most two decimals), rounds half up in cents and
creates a draft. Copies never silently activate billing.

Preview uses the selected school year's dated enrolments on an explicit date,
not the learner's current legacy class. It lists missing/overlapping plans,
ambiguous enrolments, unconfirmed legacy dates, missing homerooms and partial-year
enrolments needing a pro-rata/refund rule. It shows full annual rates, grouped by
currency, only for unambiguous full-year matches. It is not an invoice, family
statement or bill-run approval and creates no ledger entries or payment attempts.
Family payer checks, extra rule types, items, discounts and posting follow in
subsequent billing increments rather than being silently approximated here.

Every change has a durable school/operation UUID receipt and request hash;
reusing a key with changed content conflicts. Revision checks prevent stale edits.
Authorization is repeated under the school lock, including on receipt recovery.
Fresh active membership and an explicit school-role/direct permission are required;
platform support identities, impersonation and overdue password changes are refused.
Browser writes bind the displayed account and school to prevent cross-tab changes.
Plan configuration names its target year explicitly and is allowed while the
header shows a past/future year, like the new-year setup wizard. This exception
does not authorize future invoice posting or historical financial edits.
No learner/parent self-service endpoint is exposed by this increment. Responses
are private/no-store, and private validation errors never flash request inputs.

Gateway work stays provider-neutral. The owner has no gateway selected and has
explicitly deprioritized Peach-specific work. Phase 6 collection accounts stay zero.
