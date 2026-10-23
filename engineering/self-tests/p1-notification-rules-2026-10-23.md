# Notification rules verification — 23 October 2026

Feature: [notification delivery](../specs/platform/notification-rules.md).
Implementation and local automated verification are complete. Staging,
first-time-user acceptance, real phones/providers and production release remain
pending; they do not freeze unrelated development.

- Backend full run: 809 passed, two expected regressions exposed the additive v2
  context snapshot and an old test that expected the mailbox in failure logs.
  Only the three v2 context snapshots changed; v1 snapshots did not change.
- After corrections: 129 tests passed, 1,201 assertions, covering API contracts,
  log redaction, notification rules/delivery, current/ended guardians, handle
  proposals, context query budgets, daily workflows and migration rollback.
- Delivery regressions include optional/mandatory/not-offered precedence,
  wrong school/role/owner, module off, stale mailbox/device, ended membership,
  guardian rights ending after enqueue, encrypted queue payloads, per-channel
  replay deduplication and database queue rows rolling back with recipient
  claims. A worker invoked under another school still uses the delivery school.
- Pint formatted changed files; Larastan completed with no errors.
- OpenAPI strict lint passed; generated API package: 7 tests passed.
- Shared notifications package: 37 tests passed, including phone/desktop layout,
  two-tap optional email choice, required locks, errors/retry and accessibility.
- Learner/parent app: 43 tests passed. Teacher app: 26 passed. App analyzers had
  informational existing lints, without errors.
- Chromium app integration: parent signs in, opens notification settings and
  changes optional email; required absence notice stays locked. Passed locally.
- Playwright: school B admin changes a parent email default, teacher portal
  stays unchanged, absence rule remains locked and the prior setting is restored.
  Passed. New page at 1024/1280 px passed console, horizontal-scroll and serious/
  critical axe checks, without adding an allowlist exception.
- Documentation build and Playwright TypeScript check passed.

CI must pass on the PR head before merge. No cloud resources were provisioned,
no live email/push was sent and no production data or configuration was changed.
Real delivery, phones, owner review and participant timing still need staging
within the additional US$10/month limit.
