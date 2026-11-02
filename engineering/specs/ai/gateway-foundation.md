# AI gateway foundation

## Users and acceptance

An internal staff feature calls the existing AiGateway through typed
complete/embed contracts, selecting only its server-configured prompt, provider,
model, moderator, capability and budget. Given disabled/missing configuration,
wrong school/role, impersonation, invalid prompt, exhausted quota or unavailable
moderation, no provider call occurs. Students/parents are refused even if
accidentally configured. No endpoint or app screen is introduced in this slice.

Given an authorized request UUID, atomically reserve its conservative token and
integer-cent bounds under a school lock before dispatch. School, actor and
learner daily limits span all school inference features. A personal service
(`ai.inference.personal_features`: the Mastery tutor, owner decision of 31
October 2026) is neither checked against nor added to the school total; its
actor and learner limits span personal spend only
([HQ AI control centre](../platform/hq-ai.md)). Provider metadata includes only an HMAC
pseudonym and request UUID; no account/school/learner IDs or source references.
Feature callers must authorize and minimize input/source content on every call,
including replay. No counselling or learner record loader is introduced here.

Given a repeated completed UUID with the same actor/payload, return its encrypted
saved result without another call. Changed actor/input/prompt/model conflicts;
reserved, failed and quarantined operations cannot be silently re-dispatched.
Unknown provider outcomes consume the full reservation. Known pre-dispatch
refusals release it. A new UUID cannot evade already charged daily usage.

Input and output require a configured moderation implementation. Callback results
recheck tenant/authority before quarantine writes as well as before output release. Stable flags
create quarantine records and suppress output; no raw flagged content is exposed
through a review endpoint. Output protocol validation rejects malformed/non-finite
embeddings, oversized content and invalid token reports. Fresh membership,
feature configuration and tenant context are checked again before output returns.

## Data and compatibility

An additive migration extends `ai_usage` with nullable prompt/model provenance,
encrypted output/sources and reserved integer-cent cost. Existing transcription
rows and service behavior remain intact. Rollback refuses retained inference
records; empty-column rollback/reapply is rehearsed. Output/source fields are
hidden from raw model serialization. No public API/SDK change is required.

Maximum provider output tokens and conservative aggregate token reservation are
separate typed fields. Pricing is a configured worst-case cents-per-million-token
bound; accounting rounds up per request to whole cents. These are budget bounds,
not certified provider invoice reconciliation. No external adapter is configured
or shipped by this slice. Vision has a typed entry point but refuses until a
private Files-backed scanning/redaction policy is available.

## Prompts and evaluation

PromptRegistry loads bounded versioned JSON files by a restricted key and records
the exact file hash. `ai:eval <feature> --observations=<private-jsonl>` scores
recorded/adjudicated observations against bounded datasets. Missing/extra/duplicate
IDs, malformed labels and unsafe threshold configuration fail closed. Refusal,
subject-lock, safety labels and leakage canaries all have configured thresholds;
a failing score returns a nonzero process status. The command makes no network
calls and prints aggregate metrics, never observation text or private paths.

The included two-case foundation dataset is a synthetic scorer fixture. A human
can supply inaccurate labels or fabricated files; passing this offline command
is not provider qualification, a safety classifier, or the full red-team gate.
Live capture/provenance, production adapters, real moderation, per-learner access/
budgets, vision and safeguarding review/retention workflows remain later slices.

## Verification

Test happy dispatch and encrypted replay; wrong role, school and owner; accidental
learner enablement; membership revocation; impersonation; token/money reservation;
input/output quarantine; missing moderation/provider outcome handling; bounded
embedding/token output; prompt traversal; rollback preservation; and evaluation
leakage/missing/duplicate cases. Re-run existing content/transcription tests,
changed-file Pint and Larastan. No browser/profile/device result is claimed for a
service/CLI feature with no UI. Provider terms and live-model safety stay explicit.
