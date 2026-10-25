# Server quarantine extension contract

`App\Services\Ai\Quarantine\AiQuarantine::capture($usage, $stage, $flags, $content)`
atomically records a quarantined usage and its encrypted snapshot. Stage is
`input` or `output`. Content is text, null, an embedding vector (only dimensions
retained), or `{text, media:[{id,sha256,mime}]}` with at most 3 opaque photo refs.
Photo IDs are server-owned; media URLs/base64 are not accepted by this envelope.
The gateway continues to throw `AiUnavailable('inference_quarantined')` afterward.

Register a `QuarantineParticipant` by feature in `ai.quarantine.participants`.
`purgeForUsage(AiUsage)` runs inside the original school and retention transaction
without requiring the original actor/module to remain active. Implementations
must be idempotent; use the immutable operation/feature/user/school tuple to erase
racing derived copies even before usage linkage. Never delete original approved
curriculum or alter compute costs. A persisted `quarantine_purged_at` tombstone
prevents reattaching derived content after deletion. Participant failures keep the
snapshot/flags for retry; tenant replacement is refused and rolled back.

The registry is server-owned. Review authority, private photo resolution and the
school/global cost controls are separate boundaries; this internal hook grants
no user the right to read a flagged body.
