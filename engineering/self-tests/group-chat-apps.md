# Group conversation app readers

Teacher and dedicated Facilitator groups now open their actual conversation
reader/composer; Student and Parent routes opt into the corresponding fresh
persona. The existing group-detail response advertises chat availability only to
that explicit purpose. The app pins its school/account and one-way context
lease, validates returned group/audience/participant references, and keeps no
durable conversation or plaintext draft cache.

Staff choose learners, guardians or staff before publishing an announcement.
Families can ask staff privately, and replies stay private unless the school's
explicit policy and the writer both permit audience sharing. Staff replies to a
private participant carry the selected message reference and private envelope.
A message language chooser preserves the stated original language for later
source-bound translation. Generic group presence updates share the existing
protected group subscription; live invalidations trigger authorized reads.

An uncertain send keeps its immutable original UUID/body for foreground retry.
Background retirement erases text and retains only an opaque outcome reference;
metadata lookup never automatically re-sends a discarded draft. A refusal of a
previously uncertain retry does not silently turn it into a fresh write.
Moderation reasons use an inline private editor, so an independent dialog cannot
survive source retirement. Old and late bodies are discarded after account,
role, school, guardian, repository-generation or foreground retirement.

Verification:

- All 71 affected shared tests pass, including 23 new repository/widget cases and
  the existing group/register/material/message cases. New coverage includes
  390/820/1280 layouts, explicit private/shared audiences, readonly moderation,
  exact retry, uncertain-then-refused outcome recovery, background/late response
  erasure and same-account one-way retirement. The existing source-generation
  suite still passes coalesced identical-context refreshes.
- Shared package and affected Teacher/Family app analyses have zero errors or
  warnings; nonfatal style information remains. Existing case-insensitive
  translations are preserved, with explicit English fallbacks for new labels.
  Fluent locale review is not claimed.
- Actual Chrome targets pass for Teacher, dedicated Facilitator, Student and
  Parent. Each starts the real app, enters an owned group and its conversation,
  sends with an interrupted response, retries the same UUID with exactly one
  synthetic server effect, checks school/account/persona/body/query bindings and
  confirms withdrawal erases all private conversation text. The dedicated shell
  keeps authBox unopened. Its final fixture explicitly expects the fresh
  profile's Home route; it does not relax erasure checks. Two initial runs had
  only that final fixture expectation wrong; the corrected target passes.
- The Family target exits successfully for both roles. Two SSE ordering
  diagnostics were nonfatal; all assertions and final browser exception checks
  pass. These app journeys use synthetic API transport; the real HTTP/domain
  and typed protocol evidence is recorded separately in group-chat-api.md.

CI includes the three app targets with the correct dedicated flavour. This
increment does not claim the staff panel, guarded chat notice integration,
new deployed Reverb latency, native device/store acceptance or Phase 4 completion.
Those retained software slices remain tracked in the phase plan.
