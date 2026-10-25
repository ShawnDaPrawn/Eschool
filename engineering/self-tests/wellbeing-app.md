# Restricted counselling app verification — 25 October 2026

The shared private reader implements dated case and safeguarding queues,
retained histories, own-note corrections and escalations, closure/reopening,
independently scoped incident linking and appointment create/change/cancel.
School-time entry uses the trusted server IANA timezone; invalid calendar days,
DST gaps and ambiguous repeated times are refused rather than guessed.

Clinical bodies and drafts are memory-only. Backgrounding erases them. Every
MeContext refresh invalidates the previous authorization generation even when
Loading/Loaded coalesce into an identical account payload. Account/school/token
checks surround private transport, which suppresses URL/body debug logs. Unknown
write outcomes retry the identical UUID and serialized payload; known revision
conflicts reload current state and require comparison before a fresh operation.

The real counselling card is now present in the facilitator shell and inside
the classic Teacher app for staff with a clinical role. Fresh server availability
uses WellbeingAccess, separately from Support and Notes; actual case/team grants
still authorize every body. Existing Support, Notes, group and message cards
remain present. No TeacherModel grants clinical authority.

Current actual checks:

- Complete shared academic app suite: 297 tests passed, including clinical
  privacy/retry/conflict/lifecycle, appointment booking/cancellation, internal
  triage, 390/820/1280 layouts, cookie identity and same-account generations.
- Actual wellbeing/other restricted feature PHP cohort: 11 tests / 956
  assertions; fresh role/module/platform feature availability, full Larastan
  zero errors, changed-file Pint pass.
- Actual facilitator entrypoint in Chromium: sign in, open and save assigned
  case, verify account/school headers, no teacher authBox, revoke caseload,
  refresh same account and verify body erasure plus empty caseload: passed.
- Classic dual-role Teacher Chromium also passed: the real home card opens
  the assigned clinical reader, correct account/school headers are present,
  and background/resume after withdrawal erases the old body. Both browser
  flows are in the Android/Chrome CI selection with the appropriate identity.
- Final narrow unit/locale follow-up after the appointment edit refresh and
  inherited Notes key-parity repair is recorded below.

All offered language files retain exact English key parity. New clinical labels
and missing inherited Notes labels use explicit English fallback, preserving
existing translations. Fluent/clinical review remains pending. This does not
claim Android emulator/iOS compilation, physical-device testing, media, notices,
identified family reporting, staging, clinical operations or production release.

Final follow-up: all 21 clinical tests pass, including a RED/GREEN regression
proving that search/history refresh cannot silently advance an unsent draft's
revision. A changed case, report or edited appointment requires comparison
before a new write. The full learner/parent app suite passes 54 tests; the
Teacher route/locale/key cohort passes 28 after repairing inherited missing
Notes fallbacks. App/shared analyses have zero errors or warnings; existing
and new brace/style informational lints remain reported.

The app increment also consumes transfer API #144 without regenerating the
SDK, preserving its 1,327 generated files, optional risk feature flag, queue
owner names and all wellbeing definitions. Both offered app locale sets have
exact English key parity; source/whitespace checks pass.
All 82 tests for that combined generated SDK pass.
