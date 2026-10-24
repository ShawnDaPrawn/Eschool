# Phase 2 security evidence

The academic reference foundation exposes only shared grade/phase and rating
metadata. Class mappings use current-school lookup, fresh active membership and
class permissions, no mass assignment, transactional row locking and an explicit
before/after audit. Requests cannot select school_id in their payload. Archived
classes remain inaccessible. No learner marks, family records or public file
links are introduced. Legacy class serialization hides the mapping column.

Automated refusal coverage includes teacher/parent/learner, foreign and deleted
classes, missing/expired membership, inactive accounts, school switching with
cached roles, invalid input and idempotency replay. Fresh permission/account checks also run
before a saved response is replayed, including after an admin role expires. The domain service repeats
authorization without depending only on HTTP middleware. The route-wide tenancy
suite additionally supplies a foreign class in the URL.

This is source review and automated evidence, not a completed independent
security assessment or staging penetration test. Those checks and the broader
Phase 2 pilot security/operational checks remain pending; independent development
continues under the recorded delivery policy.

## School terms

Term and template operations resolve the current school before lookup, repeat
fresh permission checks before retry replay and inside locked writes, and audit
changes. Year locks serialize calendar writers; grade/date overlaps are validated
together. Permission IDs/grants and academic links survive the in-place rename.
Referenced terms cannot be removed or narrowed to a different grade range; SQL
foreign keys restrict physical deletion. Template previews do not change calendar
records; retries skip identical entries without overwriting edits.

No independent staging/pilot security test is claimed. The semester compatibility
exception comes from the explicit owner decision, not a blanket API exemption.
