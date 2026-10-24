# Bounded AI provider adapters

## Acceptance

The existing inference gateway can route a staff text request through OpenAI,
Anthropic or DeepSeek behind the same typed provider interface. OpenAI also has
an embedding adapter. No provider is enabled merely by installing an adapter:
both global inference enablement and the specific provider's qualification flag,
credential, feature authorization, prompt and budget must exist. Defaults remain
disabled/unqualified. This feature creates no new HTTP/app endpoint.

All requests use fixed HTTPS origins and allowlisted API paths. They enforce
certificate verification, three-second connect and fifteen-second request/read
limits, bounded streamed response consumption, no redirects, and no automatic
retry. Request UUID and stage form the outbound idempotency header; that header
is a client identity, not a claim that every upstream API guarantees deduplication.
The gateway's persisted receipts remain the application-level replay boundary.

Provider output and total-token ceilings are separate. Native protocol fields
carry the output ceiling; reported input/output/cache usage must fit the total
reservation. Truncated, filtered, tool-call, malformed, oversized or missing-usage
responses are refused. Returned model IDs must exactly match the configured
request model; use pinned model IDs rather than silently accepting alias changes.
Hidden reasoning is never returned. Provider errors,
headers, credentials and response bodies never enter thrown error messages.

Identifiers in provider metadata are HMAC pseudonyms only. Models are selected
server-side. Feature services still authorize and minimize all source/input data;
adapters are not a substitute for subject/guardian/counselling permission checks.
Vision is refused until the private Files-backed media policy exists.

## Moderation

The moderation interface now receives immutable operation/pseudonym context.
OpenAI text moderation uses a separate input/output stage receipt, and the same
qualification/key/transport boundaries. Its free moderation endpoint does not
report billed token usage; this adapter adds no invented provider token charge.
Input/output payloads remain bounded and both callback results are checked under
the original school before quarantine writes. Embedding vectors receive explicit
finite numeric structural checks rather than a false semantic classification.
No image is sent by this slice; suspected child-abuse media must never be sent to
this endpoint as a substitute for dedicated safeguarding handling.

## Protocol references and release evidence

Implementation follows these official protocol references, reviewed 24 October
2026; future API/model revisions require scoped protocol revalidation:

- [OpenAI chat completion](https://developers.openai.com/api/reference/resources/chat/subresources/completions/methods/create): completion-token limit, disabled storage, usage and text response.
- [OpenAI moderation](https://developers.openai.com/api/reference/resources/moderations/methods/create) and [moderation guidance](https://developers.openai.com/api/docs/guides/moderation): bounded text classification; not a dedicated CSAM service.
- [Anthropic Messages](https://platform.claude.com/docs/en/api/messages/create): native max-token field, metadata pseudonym, stop reason, text blocks and cached/input/output usage.
- [DeepSeek chat completion](https://api-docs.deepseek.com/api/create-chat-completion/): native max-token field, completion stop reason and usage.

Tests use only Http fakes with synthetic text. No credential is provisioned or
live call made. Provider contracts/regions/privacy, current model pricing, real
moderation/red-team quality, live latency and operational acceptance remain open
in the provider qualification record. Production enablement remains a separate
decision after feature-specific source and learner authorization is implemented.
