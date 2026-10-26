# Activity conversation API and typed protocol

This increment adds ten private conversation operations: policy read/write,
thread list/create/read/state, immutable message send/removal, visible read
positions and an owned metadata-only outcome receipt. The API requires an
explicit staff/Parent/Student persona. Known app tokens and portal sessions must
match that purpose; legitimate legacy account credentials still require the
fresh corresponding role and current group source. School/account checks precede
private lookup and all success/refusal responses use private no-store headers.

The existing group detail endpoint changes only for an explicit `chat_persona`
opt-in. Those responses add `chat_available`; old requests and required model
properties stay unchanged. Capability comes from dated persona-specific source
access and the current conversation policy, never management permission alone.
Reads return current reply-policy and write/reopen capabilities. Authorized
closing/moderation remains possible in readonly contexts. Expiry cleanup now also
redacts an old moderation reason after the message itself had already been removed.

Verified:

- All 598 real HTTP/domain/global OpenAPI cases pass (2,868 assertions). They cover
  a staff announcement, private Student reply and exact UUID replay, filtered
  private Parent titles/counts, legitimate dual-role access with crossed-purpose
  refusals, real portal-session purpose/account binding, wrong school/account,
  private unauthenticated/HTML validation responses without session drafts,
  policy changes, closed threads and readonly moderation. Every HTTP response is
  checked against the canonical schema. The global route catalogue includes all
  ten operations and uses actual UUID constraints in its shared samples.
- All 692 shared tenancy, role-matrix and finite private-route cases pass
  (5,287 assertions) against the pinned Composer dependencies. Whole-app PHPStan
  reports no errors; changed PHP passes Pint, and the staged patch passes the
  secret scan.
- The contract fold preserves 449 existing paths and 1,142 schemas from the combined
  assistant/Pathway baseline. It adds 26 named schemas and ten operations, plus the
  optional group-detail opt-in. Strict OpenAPI lint passes.
- Two pinned SDK generations produce 2,401 Dart files and 2,402 identical
  source/manifest hashes. All 165 SDK tests pass, including removed nullable body,
  private-reply policy, optional metadata-only receipt fields, exact UUID writes
  and existing client protocols. SDK analysis reports no errors or warnings;
  existing generated style information remains nonfatal.

The shared/panel conversation screens, generic guarded chat notices and actual
multi-profile chat/presence journeys are the next increment. No chat menu is
claimed delivered by the API alone. This does not claim Phase 4 completion,
production deployment, native device qualification or store distribution.
