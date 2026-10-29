# Learner diary

Spec: [`engineering/specs/learning/learner-diary.md`](../specs/learning/learner-diary.md).
The learner's own notes and to-dos are encrypted per learner and readable only
by that learner; homework, events and holidays are placed in the diary from
their sources on every read. The learner app has a day/week diary, private item
notes, phone reminders and a "Talk to someone" link to wellbeing reporting.

## Executed locally

- `tests/Feature/Learning/LearnerDiaryTest.php`: 10 tests pass, validated against
  `openapi.yaml`. They cover encrypted storage (no plaintext in the rows), a new
  `DIARY_KEY` keeping older text readable, retry by operation id, the 409 on a
  stale revision, permanent delete, refusal of another learner (404), Parent,
  Teacher and platform staff (403), module off (404), another school, automatic
  homework/holiday/event placement with marks, a moved due date, overdue
  carry-over, electives, private item states and strict input.
- The OpenAPI contract scenarios, the route tenancy sweep (including the five
  diary routes), the API v2 ground rules, security, routes and translation
  suites pass. Changed PHP passes Pint.
- The generated Dart client was regenerated with the pinned openapi-generator
  7.25.0 JAR (SHA-256 matching `tool/generate.sh`); only diary files differ from
  the committed client.
- The Dart code was analysed with the Dart 3.13.7 analyzer against Flutter
  3.47.7 framework sources and the locked dependency versions: zero errors and
  zero warnings in the new diary package code, its tests and the app wiring
  (the existing `curly_braces_in_flow_control_structures` infos in
  `sharedScreens.dart` are unchanged).

## Not yet executed

- `flutter test` for `packages/eschool_features/academics/test/diary/` and
  `e-school/test/diary_entry_test.dart`: this environment cannot download the
  Flutter engine, so these run in CI first.
- The learner app on a device or browser profile, reminders on a physical
  Android phone, fluent Afrikaans and isiZulu labels (English fallbacks are in
  every catalogue), the school Information Officer's POPIA confirmation,
  staging and production acceptance.
