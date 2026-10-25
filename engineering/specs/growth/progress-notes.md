# Progress bands and learner notes

Phase 3 step 2 uses canonical running/released bands, private encrypted notes,
revisioned clinical caseloads, typed v2 contracts and panel/shared application
workflows. Queued rule-aware band alerts are implemented independently in the
progress-alerts specification; external acceptance remains separately recorded.

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

Sixteen operations under `/api/v2/growth/progress` provide fresh scoped staff
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


## Private panel and shared readers

The panel workbench and shared Flutter readers implement academic notes, explicit
clinical caseloads, principal assignment metadata, retained history, author-only
revisions, archive and immediate revocation/regrant. Author text is always plain
escaped text. No note or draft is persisted by the app; lifecycle suspension
clears private state. Routes must pin account/school/context and remount after
any context refresh. Optional band unavailability does not block independently
authorized note authoring. Unknown writes retain the exact operation UUID and
payload for an explicit retry; conflicts require reviewing the current revision.

Panel private validation and historical-year refusal preserve only the current
caller's bounded draft directly in the private response, without session input
flash. Account/school and current role/caseload authorization run first. The
principal assignment view never requests clinical bodies. Family progress reads
only released periods and bands and never calls notes or caseload endpoints.
The optional ApiClient privateRequest suppresses record URLs in debug logging.

Classic Teacher home/settings entries and learner/parent home cards now open the
guarded progress routes, with school/account/child/module boundaries. The native
facilitator shell opens clinical Notes only with its fresh learner_notes feature,
and keeps Support and group navigation independent. Private mutations reject all
query parameters before Laravel can merge URL input or reflect historical drafts.
Unknown saves retain their exact memory-only intent. The shared Support reader
also opts into private URL logging suppression. Queued band notifications are
specified independently in progress-alerts.md. English,
Afrikaans and isiZulu labels retain explicit human-language review acceptance.
