# Incident reporting and restricted casework

Deliver wellbeing behind its own disabled-by-default module. Configuration is
separate from reading reports: explicit wellbeing-configure permits categories
and dated professional team metadata. A dated principal may manage case assignment
metadata. Neither path grants report, thread or case-body access. Platform staff,
impersonation and across-school contexts cannot enter professional/configuration
paths. Each professional read first uses SensitiveRecordsAccess, then fresh dated
category-team or case-specific assignment. Support-plan and learner-note grants
never grant casework access.

Anonymous reporting uses a fresh cryptographically generated secret retained by
the reporter, sent only in POST bodies. Store its keyed digest, not the secret,
and no reporter, IP, device or user-agent identifier. Once explicitly identified,
that secret alone stops granting access: subsequent views also require the named
reporter and current account/guardian scope. A school-scoped token gives
only its own report and public follow-up thread. The first submission is safely
retryable with the same secret and payload; a changed payload requires explicit
new follow-up. Never put the secret in a URL, notification, audit or plaintext
session flash. Readable emergency guidance is shown before submission and every follow-up; it
does not require an acknowledgment checkbox. This is not an emergency-response
service. Switching to identified requires explicit
consent plus fresh account/learner/guardian authority. Identity never appears
merely because a reporter happens to have an authenticated cookie.

Professional reports/threads, case entries, appointment details and operation
receipts are encrypted. Triage team allocation does not silently grant case
access. A case has separately dated named professionals; entries and escalations
retain immutable history. Appointments check overlaps under a school lock and
changes use optimistic revisions and stable operation UUIDs. All replay paths
recheck current authority. Retained history blocks destructive rollback.

Implement domain and privacy tests first, then public phone-friendly reporting,
restricted panel and facilitator screens with additive API/SDK contracts. Media
will pass the existing scanning/private-file pipeline after image metadata is
removed; no unrestricted generic file IDs or external URLs are accepted. Do not
claim attachments until that complete path is delivered. No case text enters
generic audit/search/dashboard, teacher menus or notifications. Notices contain
only a generic action link and require authority again when opened.

Verification must cover anonymous storage, keyed token scope, lost-response replay,
wrong school/token/category/owner, dates and revocation, platform identity and
impersonation, no admin-body fallback, independent caseloads, consent, encrypted
storage, large receipts, revision conflicts, appointments and rollback. Browser,
physical-device, staging and ingress-log privacy qualification are tracked as
separate evidence rather than inferred from service tests.

Anonymous write acknowledgments are immutable and contain no report identity,
body or anonymity-status snapshot. The reporter reads current state separately;
retrying an old follow-up cannot falsely present a previously anonymous status
after explicit identification. Public thread mutations deduplicate UUIDs within
the token-scoped report. Professional mutations use encrypted, school-scoped
operation receipts and current authority before replay.
