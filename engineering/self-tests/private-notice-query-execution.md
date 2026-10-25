# Fresh private notice queries

An actual MySQL consultation regression reproduced a retained authority window:
a NotificationVisibility builder constructed while a guardian link was active
still counted the source notice after that link ended. The old query returned
1, expected 0 (11.36 seconds, one failing assertion).

The candidate registers a pinned Eloquent scope instead of freezing provider
results when the inbox builder is first constructed. Each query execution,
including pagination count and row reads, refreshes the actor and registered
source policy. Initial school/account validity and impersonation/across-school
refusals remain pinned; restoring a normal context cannot authorize a query
constructed under a denied context. Existing source SQL predicates remain in
place. Prefix, recipient and school guards stay outside a provider's OR clauses.

New generic regressions cover an imperative provider's changing allowed-ID set,
repeated count/lookup execution, membership withdrawal and initially denied
password/impersonation/across-school contexts. The existing source-specific
consultation test also checks guardian and role conversion after preparation.
All 10 shared private-scope and all 8 generic staff inbox cases pass. The
source-specific consultation regression also passes, including guardian and role
withdrawal after preparation. Whole-app Larastan (512M) reports zero errors;
Pint passes.

The final consultation-notice cohort separately passed all 11 cases / 195
assertions, including real queued-email suppression. The electorate cohort also
passed all 5 cases. The initial mail test's incorrect delivery-row lookup was
corrected to the actual queued job's delivery key; no mail transport policy was
weakened. Existing explicit source SQL predicates remain additional protection.
