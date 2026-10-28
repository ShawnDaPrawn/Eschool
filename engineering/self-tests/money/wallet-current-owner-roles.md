# Current wallet owner roles and retained learner history

Wallet classification and control authority now use current school roles. An adult
with a retained student row keeps their own adult spending controls. Historical
guardian links do not expose that adult's wallet, including after their adult
school membership ends. Inactive former learners retain the existing authorised
guardian history path. Filtering the accessible-wallet list also keeps an old
guardian association from blocking the parent's other wallets.

The two targeted cases plus existing personal-wallet and Parent transfer suites
passed 24 tests / 189 assertions. Coverage includes adult own controls, active and
departed adult refusals, retained learner history, current learner restrictions,
wrong owner/school/role and guardian revocation. No ledger or financial history
was edited. Fresh Pint and Money PHPStan are qualified before publication; hosted
exact-source CI/security/review and main integration remain pending.
