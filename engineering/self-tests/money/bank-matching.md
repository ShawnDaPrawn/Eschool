# Bank matching verification

Bank-neutral CSV review/retention, exact-reference suggestions and reviewed
new/existing EFT receipt matching are implemented. Unmatched evidence remains a
queue; reasoned non-fee exclusions do not post money. Final local/hosted evidence
and integration are tracked on the feature PR. Unrun checks are not claimed passed.

Initial local PHP 8.3/MySQL checks passed **16 workflow tests /146 assertions**
and **24 CSV cases /28 assertions**. They cover repeat/overlapping/reordered imports,
changed transaction-ID conflicts, key rotation, exact receipt linking, stale and
forged reviews, payer/pin/currency ownership, late-failure atomic rollback, database
history guards, populated rollback refusal, actual foreign IDs, role/actor/module
binding, private forms and incomplete-history reconciliation. Parser cases reject
invalid dates, signs/precision, currency, duplicate IDs, malformed quoting,
non-UTF-8/NUL content and file/row bounds.

Provider-specific bank format qualification, external bank/statement observation,
physical-device/staging acceptance, fluent language review and independent
live-money assessment remain separate from source completion and safe merges.
No bank connection, gateway, live transfer or production activation occurs.


Finance browser qualification passed **7 tests**, zero automatic retries. Both
new-receipt and existing-receipt journeys passed at390/820/1280px, including
reopened/duplicate import confirmation, separate allocation, reasoned debit
exclusion, queue filters, exact final ledger counts, serious/critical axe,
horizontal overflow, duplicate IDs, no-store headers and console/HTTP errors.
Teacher access was refused. A first run caught the wrong shared upload component;
the final run uses the existing M3 file-field without weakening assertions.
Baseline-free money level5 and browser TypeScript analysis passed.


The expanded local Money/Bootstrap/parser/share cohort passed **139 tests /2,386
assertions**. Review found that JSON Unicode escaping could expand an otherwise
valid400-row UTF-8 file past the confirmation limit. The reproduced old payload
was852,124 characters; compact Unicode/slash/line-terminator encoding reduces it
to404,124 without relaxing file/form bounds. A400-row accented/Unicode-separator
import-confirmation regression is included in final source qualification on the PR.
