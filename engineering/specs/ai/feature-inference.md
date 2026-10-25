# Feature-authorized inference

Learner and parent inference uses a separate, server-registered feature policy;
the existing internal `run` contract remains staff-only. No role/config toggle
may create a general Parent/Student provider access path. The default registry is
empty and inference/provider qualification remains disabled.

`runFeature(User, Capability, FeatureInferenceRequest)` resolves an immutable
`AuthorizedInferenceScope` through `InferenceFeaturePolicy`. Policies expose their
fixed feature name, resolve fresh membership/guardian/subject/source authority
before loading private sources, and reauthorize the exact scope around every
external stage and receipt replay. Sources must be minimized, immutable version
references, approved for the specific purpose; no counselling/case/flag content or
arbitrary school-wide retrieval enters a general assistant. Domain policies own
source selection, subject lock, approved prompt, and content provenance. Generic
requests never accept a pre-authorized scope from a caller.

The gateway adds its own school/actor/scope consistency, learner ownership and
guardian approval checks, learner daily token/cents caps alongside actor/school
caps, and learner pseudonyms upstream. Usage reuses the existing nullable learner reference without rewriting history. Feature text calls share an overall deadline of at
most ten seconds across moderation and inference; adapters clip each HTTP stage
to the remaining allowance and never retry automatically. Vision remains closed
until a scanned/private/redacted Files policy and tested media envelope arrive.

All provider/moderator qualification and bounded request validation is preflighted
without network before external moderation. A typed known-not-dispatched refusal
settles zero compute even if qualification changes before HTTP. Unknown transport
outcomes keep the full conservative reservation. A validated provider result
settles its known usage even when output moderation quarantines the result.

Tests cover a registered synthetic feature, default deny, staff API compatibility,
wrong role/school/learner/guardian, withdrawn approval/source/role, replay, caps,
pseudonym metadata, deadlines, qualified moderator with unqualified provider and
zero HTTP/usage, and unknown post-dispatch outcomes. HTTP fakes establish protocol
behavior only. No provider is enabled, no paid call is made, and real privacy,
safety/cost/latency qualification is recorded separately.
