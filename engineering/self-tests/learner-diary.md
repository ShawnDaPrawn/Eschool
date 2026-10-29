# Learner diary

Spec: [`engineering/specs/learning/learner-diary.md`](../specs/learning/learner-diary.md).
The learner's own notes and to-dos are encrypted per learner and readable only
by that learner; homework, events and holidays are placed in the diary from
their sources on every read. The learner app has a day/week diary, private item
notes, phone reminders and a "Talk to someone" link to wellbeing reporting.

## Executed locally

- `tests/Feature/Learning/LearnerDiaryTest.php`: 14 tests pass (332 assertions), validated against
  `openapi.yaml`. They cover encrypted storage (no plaintext in the rows), a new
  `DIARY_KEY` keeping older text readable, retry by operation id, the 409 on a
  stale revision, permanent delete, refusal of another learner (404), Parent,
  Teacher and platform staff (403), module off (404), another school, automatic
  homework/holiday/event placement with marks, a moved due date, overdue
  carry-over, electives, private item states, the upcoming-reminders list (done,
  past and handed-in items excluded, one learner's reminders never another's)
  and strict input.
- The OpenAPI contract scenarios (675), the route tenancy sweep (including the six
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

- An independent read-through of the app code against the Flutter 3.47.7
  sources found one test that would hang, editor text that could outlive a
  closed session, a background clear that ran after frames stopped, and
  reminders that missed moved, done and unviewed entries. All are fixed: the
  diary has its own navigator inside the session boundary, it is covered and
  cleared from `inactive`, and reminders are replaced from the server's full
  list, with a sign-out guard.

## Merge-review regressions (29 October 2026)

- School-local diary range boundaries and due wall times now use the same timezone as school-local today. The API regression freezes a Monday in a UTC application and an Africa/Johannesburg school, carries Sunday 23:30 homework to Monday, and preserves it on Sunday in a historical range.
- Read-only entries now open a full-text reader without mutation controls. Widget regressions cover both long notes and long to-dos and assert that no updates or deletes occur.
- All 14 backend diary tests passed locally (332 assertions). All 18 diary widget tests passed locally; analysis of the changed Dart files found no issues, and changed PHP passed Pint.
- The preceding head472e8b0 passed all125 CI checks, including the diary package and app-entry widget tests. The final correction head must finish its own CI before merge.

## Not yet executed

- The learner app on a device or browser profile, reminders on a physical
  Android phone, fluent Afrikaans and isiZulu labels (English fallbacks are in
  every catalogue), the school Information Officer's POPIA confirmation,
  staging and production acceptance.

Save-recovery follow-up: the editor retains attempted date locations when a write reply is lost, so a changed date does not hide the saved row from recovery. Recovery also includes the known completion date and a short window covering school/device calendar differences for undated to-dos. The regression fake can enforce the server range filter; the moved-date lost-reply widget case passes with one update and no stale-revision loop. Final local qualification: 14 backend diary tests/332 assertions, 18 Flutter diary tests and clean diary analysis. Final CI remains required before merge.
