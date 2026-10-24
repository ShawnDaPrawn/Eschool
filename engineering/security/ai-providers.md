# AI provider qualification

The Phase 3 foundation activates no external inference provider or feature.
The existing Phase 2 local transcription adapter remains unchanged.

| Candidate | Adapter in first foundation | Contracts / no training | Processing locations | Live evaluation | Enabled |
| --- | --- | --- | --- | --- | --- |
| OpenAI | Not implemented | Unreviewed | Unconfirmed | Not run | No |
| Anthropic | Not implemented | Unreviewed | Unconfirmed | Not run | No |
| DeepSeek | Not implemented | Unreviewed | Unconfirmed | Not run | No |

Before a feature is enabled, record the exact provider/model/version, no-training
terms and POPIA review, processing locations, credentials through approved secret
bindings, timeout/idempotency implementation, current conservative prices, caps,
moderation, feature-specific source/subject authority and acceptance evidence.
No candidate is approved merely by appearing in this table or implementing a
PHP interface. Provider changes require scoped requalification.

Protocol unit tests use test-only adapters and synthetic data. The recorded-output
scoring command does not contact providers or authenticate the provenance of
operator-supplied observations. Its passing fixture checks are not live-model
red-team/safeguarding acceptance. No provider cost or safety claim is made here.
