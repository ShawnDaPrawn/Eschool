# Card identity and cryptography qualification

This increment implements the school-scoped card identity domain, encrypted
private sanitized portraits, short-lived signed digital credentials, daily key
rotation, immediate owner/guardian freeze, and atomic replacement with retained
history. Explicit issuance permissions do not reuse teacher or facilitator
academic rights. Current learner membership, enrolment and approval are checked
even when the legacy approval-enforcement switch is disabled.

Validation: 26 tests, 866 assertions using real MySQL migrations and transactions,
private encrypted filesystem writes, real image sanitization, signed credentials,
key rotation/expiry, replay receipts and role/owner/school/approval boundaries.
The upload scanner is synthetic in these tests; production scanner acceptance is
not claimed. Whole-application Larastan and Pint pass.

Cryptography includes RFC 4493 AES-CMAC and RFC 8032 Ed25519 known answers plus
NXP AN12196 Rev. 2 tables 1, 4 and 5 (encrypted PICC metadata, little-endian
counter, session derivation and truncated MAC). These published vectors qualify
the verifier calculations; they do not qualify a physical encoded card or reader.

Remaining in the card feature: authenticated reader/device workflows, atomic
online QR nonce and SUN counter consumption, bounded offline snapshots and
reconciliation, encoding runtime/confirmation, restricted classroom sessions,
API/SDK, panel/app screens, CR80 exports and actual application flows. Their
storage tables are foundations, not delivered runtime capability. No hardware,
printer, store release, production service or Phase 5 payment acceptance is
claimed. The existing teacher/v1 card flows remain separately available.
