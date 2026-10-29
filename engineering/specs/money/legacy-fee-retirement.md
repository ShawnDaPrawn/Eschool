# One financial source

The owner authorized retiring the abandoned seed fee records, with no migration
into the new immutable ledger and no payment gateway integration.

Bundled Family fee entry points now open the permission- and module-aware v2
statements/receipts. Academic reports remain academic. Saved fee-lock links return
to learning. No fee balance can block learning. The old checkout, SDK, payment
services, panel collectors, class-fee transfers and gateway callbacks are removed.

Installed v1 clients receive an explicit update-required error envelope (code410)
from their existing authenticated, role/child-scoped fee routes. PDF endpoints
also return HTTP410 so an error cannot be saved as a receipt. Old panel list
bookmarks redirect to current fee items/plans/payments; numeric legacy receipt
IDs are never reinterpreted as canonical IDs. Gateway availability flags are off.
Distinct academic/history/import capabilities remain until parity is established.

Home reports gross posted receipts received within the selected school year's
bounds, grouped by ISO currency, including held credit. This is not current debt
or net receipts after refunds. Leadership reports currently applied learner
allocations by receipt date, less released allocations; unassigned family charges
and held credit are excluded. Dated enrolment attributes a learner to their grade.
Ambiguous enrolments, currency mixtures and invalid/overflowing cents are not
silently added. Finance statements remain the detailed source.

School-year creation/editing no longer writes seed fee due dates, late charges,
installments or learning-lock dates. Financial setup uses explicit current-year
plans and family accounts. A year with canonical plans (including archived plans)
or bill runs cannot be deleted through academic cleanup.

## Rollout and rollback

This release removes active legacy code, not historical migrations or schema.
There are no destructive migrations. The prior Cloud Run revision must be able
to serve during deployment and rollback. Inert legacy tables/models, cents-column
migration metadata and secret-audit tombstones are retained for that contract;
they are not a second supported money system. Do not delete or recalculate new
ledger, invoice, allocation, receipt, refund or permanent outcome history.
A later schema-contract release can remove obsolete physical structures after
all running clients/revisions are confirmed clear of them.

Obsolete fee permissions are no longer presented as current role capabilities.
Role writes accept the current offered permissions only, without mapping retired
grants into broader new money privileges. Historical permission definitions remain
in the database for rollout compatibility; new authority is an explicit role choice.
