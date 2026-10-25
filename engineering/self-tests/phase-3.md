# Phase 3 verification record

## AI gateway staff foundation

Implemented typed completion/embedding contracts and a vision entry point that
refuses until private media policy exists; versioned bounded prompts; fresh
school/membership/staff-role checks; impersonation exclusion; atomic school/actor
token and conservative integer-cent reservation; private encrypted output/source
receipts; exact UUID replay/conflicts; input/output moderation interfaces;
quarantine flags; protocol validation; conservative failed-outcome charging; and
recorded-observation evaluation with nonzero failure status.

Production inference is disabled and has no configured feature or live provider.
The Phase 2 local transcription implementation remains intact.

Automated verification: **42 tests passed, 502 assertions** across
`InferenceFoundationTest` and the existing `ContentEngineTest`. This covers
happy dispatch/replay, wrong role/school/owner, accidental Student/Parent settings,
revoked membership/context replacement, impersonation, caps, quarantine, unavailable
moderation, uncertain outcomes, invalid embeddings/token reports, prompt traversal,
retained-data rollback refusal and empty rollback/reapply. Evaluation tests catch
leakage, missing/duplicate cases and disabled leakage thresholds. Changed-file
Pint and the complete configured Larastan analysis pass. Final-head CI belongs on
the feature PR and remains required before merge.

A subsequent focused run passes **15 tests, 80 assertions** and checks the final
context recheck before output moderation;
it prevents invoking a school-specific moderator after context replacement or
membership revocation. The source still rechecks again before releasing output.

No new HTTP endpoint or app/panel screen exists in this slice. No live provider,
vision/media, per-learner authorization/budget, counselling access, safeguarding
review/retention workflow, real-model safety evaluation, device, staging, cost
observation or production acceptance is claimed. Prompt/model provenance is
stored for completed inference, but the sample prompt is only a staff-draft
foundation, not an approved tutor or parent prompt. The two-case evaluation
fixture and test-only adapter responses are protocol evidence only.

Review follow-up: moderation callbacks can also change the current school.
Both input/output moderation results now recheck the original school and fresh
authority before any quarantine flag is written. Final focused run: **16 tests
passed, 93 assertions**, including both callback directions, zero foreign flags,
original-school settlement and suppressed output. Changed-file Pint passes.

## Provider and moderation protocol adapters

Implemented fixed-origin HTTPS protocols for OpenAI text/embedding, Anthropic
Messages and DeepSeek text, plus OpenAI text moderation. Separate output/total
budgets, exact returned-model checks, bounded response streams, TLS verification,
timeouts, no redirects/retries, sanitized errors, pseudonymous metadata and staged
UUID headers are covered by synthetic Http fakes. Moderation callbacks now receive
operation/pseudonym context; existing tenant rechecks remain in place.

Final focused protocol/foundation run: **24 tests passed, 186 assertions**.
The existing 27 content-engine tests also passed with the updated moderator
interface. Changed-file Pint and full configured Larastan analysis pass.
No real endpoint was contacted and no provider was enabled or qualified.
These are protocol tests, not real-model safety, privacy, latency or cost evidence.
See the provider adapter spec and qualification record for the release boundary.
