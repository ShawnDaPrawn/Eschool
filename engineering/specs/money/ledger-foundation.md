# School ledger foundation and Phase 3/4 handovers

This preparation increment introduces an internal kernel. It does not enable
wallets, vendors, a new payment method, collection on behalf of schools, billing,
or an admission payment integration. Domain services must authorize actor, role,
owner, approvals and business transitions before calling the kernel. There is no
new public posting endpoint. Existing v1 financial contracts remain intact.

## Posting and balances

`LedgerService::post(key, source, description, postings)` uses integer cents and
one explicit currency. Positive debit/credit lines must balance, contain 2–100
postings, and fit the supported integer range. An immutable account has a school,
type, stable reference, normal direction, currency, module and optional person.
A person must have an active school membership when opening their account.
Shared cash-desk/clearing/due-to-school accounts fix their owning financial module
explicitly at creation (fees by default for fee callers). Wallet cash accounts
use `module: wallet`, so wallet-only schools never require the fees switch.
Wallet, vendor and family/fee-receivable types retain their own module ownership;
an existing reference cannot be reassigned to bypass a disabled switch.

Cash-desk, receivable and clearing accounts are debit-normal; family, wallet,
vendor and due-to-school accounts are credit-normal. Each wallet's balance must
stay nonnegative. Clearing and due-to-school accounts can be opened at zero but
cannot receive postings until the later collection phase is implemented.

Posting locks the school then the affected accounts, in a transaction. All money
module/package switches lock the same school. Authority and financial history
use current locking reads, including when called inside an existing repeatable
read domain transaction. The wallet check uses immutable history and verifies the
cached projection; a corrupt cache cannot grant extra spending power. Different
schools remain independent. This initial school-wide write serialization favors
correctness; measure throughput before narrowing locks.

A key is permanently unique per school, with a canonical request hash. Exact
replay returns the original posted entry even after a module becomes read-only;
changed content conflicts. A separate permanent school/source identity prevents
a new key from reposting the same business receipt. Domain integrations must
choose a stable source per financial action, never a random retry reference.
The 24-hour HTTP idempotency cache is not the durable receipt.

MySQL/MariaDB cannot express a cross-row sum as a row CHECK. The database instead
permits a draft only while its transaction assembles postings, and a finalization
trigger requires at least two lines and net zero before `posted_at` is set. Tenant
composite foreign keys and a currency/sealed-entry insertion trigger protect the
posting context. Finalized entries and all postings reject updates/deletes;
account identity rejects reassignment. All canonical instants are UTC. An
exception rolls back the draft, postings and projected balances together. Do not
commit draft entries outside this service. A reconciliation reports any orphaned
draft as an error. Rollback refuses retained history; it never deletes money to
make a down migration succeed.

`balance()` derives from finalized postings. `ledger:reconcile --school=ID --json`
is read-only: it reports incomplete/unbalanced entries, projection differences
and school-scoped legacy float/cents discrepancies, including retained rows. It
runs nightly and emits `money_ledger_reconciliation_failed` at critical severity
on failure; operations must connect that signal to their existing alert route.
`--rebuild` explicitly repairs projections from valid immutable history only;
it does not backfill cents, import opening balances, alter receipts or repost
history. Existing `money:backfill-cents` remains a separate mutating command.
Neither command proves statements agree with a school's bank/cash records.

## Module and identity boundaries

A financial module with ledger history or a retained balance becomes read-only
when disabled or removed from a package, including an emptied wallet with
history. A different school's history never retains this school's module.
Opening an unused zero account alone does not retain a module. The kernel
refuses new postings to unavailable/read-only modules. Future wallet/vendor
modules remain unregistered until their actual capabilities exist; tests use an
explicit synthetic module configuration, not a shipped entitlement.

Entry approval, offline card signatures and a photo prove identity/access;
they do not authorize financial spending. Future card/QR payment requires a
separate online, actor-scoped financial operation and limits, a frozen-card
check and a stable ledger intent; no balance is stored on a card. Existing gate
passes/offline readers must not be repurposed into wallet authorization.
The vendor native build identity remains unavailable until distinct vendor
roles, server ownership and order/settlement access exist; Teacher/Fac access is
never its fallback. No vendor screen or spend route is enabled by this increment.

## Cutover and retention

Admissions office-fee acknowledgements remain audited administrative facts,
not a new ledger receipt. Leadership continues reading its existing reconciled
settled-receipt projection. See [the explicit cutover contract](admissions-receipts-cutover.md)
before either starts consuming the ledger. Imports need a reviewed per-school
source mapping, trial reconciliation, a resumable unique identity and one
financial source of truth per receipt; combining both representations must never
count one receipt twice. Old financial rows/floats stay until the reconciliation
and cutover evidence actually exist.

Academic transfers move consented academic records and date memberships. They
never automatically move debts, wallets, fee-visibility rights or either
school's financial history. Any future refund, withdrawal or inter-school
settlement is a separate authorized money workflow.

Verification is recorded in [the preparation self-test](../../self-tests/money/preparation.md).
