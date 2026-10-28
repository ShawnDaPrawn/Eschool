# Automatic fee item rules and reviewed batches

Finance chooses manual, compulsory-by-rule or membership-driven charging per
existing fee item. Grade/class/phase and eligible profile rules reuse the annual
plan rule engine. Canonical dated activity groups cover clubs, teams and transport
routes; dated teaching-group memberships support groups and subjects. No undated
legacy subject roster is treated as consent to bill. Invalid/legacy enrolment dates
are explicit exceptions. Inactive or out-of-date memberships do not qualify.

The same eligibility calculation guards both automatic batches and manual item
charges. A family item qualifies when any assigned learner matches, then charges
once with its complete family/holder allocation reviewed. A learner item requires
that learner. Batch review shows new invoices, missing/ambiguous payers, other
exceptions, nonmatching learners and links to already charged occurrences. Finance
must explicitly accept excluding exceptions. No target posts without confirmation.

Posting rechecks rules, memberships, guardian/payer rights, current item price/VAT,
and exact charge fingerprints under the school lock. Every child invoice uses the
existing immutable FeeCharges posting path. The entire batch and all child durable
operation receipts roll back on a late error. Root replay returns the same batch;
a different UUID cannot rebill the same item/year/occurrence/learner-or-family.
The immutable batch retains original review metadata and exact sealed charge IDs;
raw private occurrence references remain encrypted on the original charges.

Finance can configure rules and prepare/review/post batches through existing item
permissions. Disabled fees retain history and refuse writes. Real other-school
sources and records, stale form bindings and fresh permission revocation must be
refused. Rules are revisioned; SQL enforces source/target tenancy and immutable
batch count/amount/charge identity. Existing no-rule/manual history stays valid.

Opt-in consent and its parent UI are the next part of increment4. This PR covers
compulsory and membership automation only, with no implied parent consent. The
remaining discounts/refunds/wallet/commerce work continues separately. Gateways,
production activation and extra spend remain outside this change.
