# Encrypted AI quarantine capture and retention

Flagged input/output is captured once per usage/stage in an encrypted snapshot;
categories point to it without duplicating the private body. Captures validate
all categories and bounded content before changing state. Exact captures retain
their original content and expiry; changed-body retries are refused. Vision
payloads retain bounded text and opaque server-owned photo references/hash/MIME,
never raw image data or URLs. Embedding flags retain only the vector dimensions.
No new public reader, reviewer grant, provider or feature activation is added.

`AiQuarantine::capture(AiUsage, stage, flags, content)` is the internal hook.
The inference gateway preserves `inference_quarantined` and its existing
accounting behavior; local transcription uses the same capture. Retention
participants implement `purgeForUsage(AiUsage)` and are allowlisted by feature in
`ai.quarantine.participants`. They must remove only exact feature-owned derived
copies, correlate usage/operation/user/school, and reject late linkage after
`quarantine_purged_at`. Original approved curriculum and metering history stay.

The conservative default retains flagged copies for 30 days maximum; server
configuration can shorten it. `ai:prune-quarantine` runs daily and drains bounded
batches across all schools, including inactive schools/disabled features. It
clears flagged snapshots and derived inference copies without refunding tokens or
cents. Participant failure rolls back the deletion for retry. A participant
changing tenant is refused and the original context restored. Review consumers
must apply expiry before body access; the restricted reviewer UI and role/cap
boundary remain a separate implementation increment, not an implicit access grant.

Native MySQL: initial domain+school inventory 10 tests / 829 assertions pass; combined
quarantine/gateway/feature-policy/transcription 62 tests / 622 assertions pass. Final
quarantine-only 8 tests / 42 assertions pass after the bounded-draining command change.
Whole-application PHPStan reports no errors; Pint passes. Tests cover ciphertext
and hidden serialization, immutable capture, malformed categories/media,
wrong-school and impersonation, exact participant identity, purge failure and
tenant substitution, historical category-only retention, terminal purge and
unchanged compute costs. No provider network call or production data was used.
