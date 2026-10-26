# Scoped group conversation domain

This increment implements the domain and database only: current-persona/group
access, encrypted immutable threads/messages, private family questions/replies,
explicit peer-sharing rules, moderation, close/reopen, monotonic per-persona read
positions, metadata-only UUID receipts and retention. It reuses activity outbox
invalidation without putting text or names on live channels. A private owner is
bound to both account and persona; a dual-role account cannot reopen a private
staff-purpose thread through its retained Parent identity.

Verification: 14 tests / 1,307 assertions pass (ten conversation workflow/security
cases, three table inventory cases and the complete tenancy down/up/backfill
rehearsal). Coverage includes exact replay and changed-text conflict; wrong
school, role, owner, group allocation, platform/across-school refusals; retained
Parent/Student rows after conversion; legitimate explicit dual-role allocation;
dated ancestor-admin cascade; peer-policy withdrawal; private SQL counts/text;
closed conversations; readonly moderation; encrypted at-rest body/title/reason;
rollback; and expiry hiding/redaction independently of module availability.
The subsequent affected conversation/file cohort passes34 tests /282 assertions,
including the existing global learner-activation rule for a dual-role staff source
reader after approval withdrawal. The real material-file API readonly removal
regression first returned403; read-mode module routing now permits its authorized
removal while the domain still refuses new uploads before scanning/storage.
Whole-app PHPStan reports no errors; changed PHP passes Pint. The schema adds four
school-owned tables with foreign keys and refuses destructive retained rollback.

The command redacts bounded batches under the same school lock as writes,
including inactive schools/modules. Reads independently exclude expired content,
so cleanup delay does not reopen expired text. Existing chat size/retention
settings are bounded to10,000 characters and365 days. Sender labels are resolved
only within an authorized message reader. Source references for later translation
use the exact authorized message, language, revision and keyed body fingerprint.

Dedicated API/SDK, shared app/panel conversations, generic guarded notices and
actual end-to-end chat/presence journeys remain implementation work. This is not
a completed group-chat app or a Phase4 completion claim. No new external service
or device acceptance is inferred from these MySQL tests.
