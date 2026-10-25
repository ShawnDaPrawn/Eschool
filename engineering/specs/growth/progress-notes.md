# Progress bands and learner notes

This increment implements Phase 3 step 2 domain behavior. HTTP contracts, panel
and app workflows and queued rule-aware alerts follow in a separate increment;
this domain increment alone does not complete the feature.

Running marks use existing immutable mark snapshots. Thresholds default to 80%
and 65%, with a configurable minimum assessed share defaulting to 25%. Below
that share, evidence is insufficient rather than a low band. Staff may see
current scoped marks; family reads use released canonical snapshots exclusively.
Settings use optimistic revisions and integer basis points.

Academic note bodies and revision history are encrypted. Visibility is immutable:
tutor-visible, staff-only or counselling. Current dated teaching allocation or
dated academic leadership must cover the learner and optional subject. Authorship
alone never preserves access after scope revocation. Counselling additionally
requires the shared dedicated-role safeguard and a current note-only caseload
assignment. A current principal may administer assignment metadata without body
access. No clinical text or clinical topic/needs labels enter family, tutor,
progress or leadership aggregates. No automatic platform/Super Admin access.

Writes use the current writable school year, explicit revisions and transactions.
Historical note bodies remain retained. This library exposes explicit policy
checks before bodies are loaded and does not flash text into an HTTP session.
The subsequent private forms must register the shared private-year refusal hook.

## Private v2 contract

Fifteen operations under `/api/v2/growth/progress` provide fresh scoped staff
configuration/rosters, principal assignment recipients, own counselling caseload,
current staff bands, approved own-family released periods/bands, note metadata,
explicit body/detail/history, note create/update, principal caseload writes and
band settings. The owned contract is `engineering/contracts/progress-notes.openapi.yaml`.

Writes require an operation UUID and use a separate encrypted LONGTEXT receipt
ledger. A keyed intent digest protects short private text from database-only
hash guessing. Every retry rechecks role, school, scope, year and assignment;
clinical bodies never enter the global plaintext API idempotency cache. Scope,
author and visibility are immutable. The ledger pins and restores school context
around authority callbacks. Expected-account headers reject replacement cookies
before tenant data retrieval. Every response forbids caches.

The facilitator profile has optional support_plans/learner_notes booleans,
without defaults or new required fields: older sessions preserve omission.
Availability exposes no case or note bodies and grants no per-record authority.
