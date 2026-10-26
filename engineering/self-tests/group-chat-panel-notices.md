# Group conversations: staff panel and guarded notices

Qualified implementation, pending review/CI/main integration.

Actual local evidence on 26 October 2026:

- Combined panel, chat API/domain and first notice cohort: 30 tests / 418 assertions passed.
- Expanded notices, panel, existing consent notices/staff inbox and UI style guards: 31 tests / 304 assertions passed.
- Additional queued peer-sharing withdrawal: 1 test / 6 assertions passed.
- Family notification recipient/role/module/deep-link actions: 11 widget cases passed.
- Classic Teacher recipient/role/module/deep-link actions: 11 widget cases passed.
- Dedicated Fac inbox action opens the actual group/conversation reader, checks school/account headers and erases withdrawn scope: 2 cases, 390/1280px, passed; authBox remains unopened.
- Actual Playwright allocated-Teacher workflows at 390/820/1280px: all 3 passed in 50.7s. Compose, real Parent API private reply, private staff response, reasoned removal, close/reopen, accessible labels, axe/IDs/overflow and actual allocation-withdrawal DOM erasure were asserted. Only the exact expected lease404 is accepted.
- Selected PHPStan on all 11 changed PHP source files: zero errors at 512 MiB.
- TypeScript e2e source type-check, Pint and CI actionlint passed.

The attempted complete PHPStan run exhausted its agreed 768 MiB ceiling while
writing the result cache; it is not reported as passed. CI retains full-app
analysis. The final native guard cohort passed 26 cases / 785 assertions with one inherited
Pathways navigation-label failure. The exact separately qualified fix #293 is
now inherited by semantic catalogue union; its own 9 navigation cases / 641
assertions passed. No existing translation, assertion or authorization was
removed. A final combined rerun and whole-app analyses remain CI checks.

The new notice scope checks current school/account/role, approved current learners,
linked guardians, dated staff/group allocations, allowed audience combinations,
retention and source revision before fanout, queued transport and prepared inbox
execution. Dual-role HTTP list tests keep Parent/Student persona counts apart.
Legacy null/unknown notice types and optional JSON fields retain their behavior.

Private text, titles and participant names never enter notice payloads. Source
keys make per-message/persona fanout idempotent. Native tests exercise current
withdrawal at actual queued delivery, while app-action widgets and the existing
four actual app conversation journeys cover their separate reader boundaries.
No actual mail/push provider, new Reverb latency measurement or physical native
device journey is inferred from these checks.
