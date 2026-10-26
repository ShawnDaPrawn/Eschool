# Exam coach private API verification

Seventeen operations expose the licensed paper library and own learner study
plans: configuration; allocated staff choices, paper draft/revision/publication/
withdrawal and private paper/memo PDFs; current learner published papers, own
exam dates, exact plan creation, list and read. Private writes reject query
content and unknown fields. Every response/refusal uses private no-store,
no-referrer and nosniff headers. Every request supports expected-account binding.

The real HTTP and whole contract cohort passes **516 tests / 2,694 assertions**.
It validates staff/Student responses, immutable replay, memo exclusion, licence
expiry/withdrawal, old drafts hidden from other teachers, fresh allocation,
guardian, account/school and source withdrawal, actual PDF downloads and changed
exam schedules. All seventeen routes reach authentication with private headers.
The fresh complete route-tenancy/role matrix also passes **620 tests / 4,711
assertions**. Final selected API static analysis passes.
The API source/domain cohort previously passed 21 tests / 270 assertions and
whole-app PHPStan; no API/schema exception is added for these routes.

Pinned OpenAPI Generator 7.25.0 produced **2,111 Dart files**, twice byte-identical
including its manifest. The existing 133 SDK tests pass; four Exam model/request
cases pass after correcting the test harness to decode Dio's serialized JSON
body. Learner memo omission, required-null staff learner ID, date-only strings,
version pins, school/account headers and exact body intent are covered.
SDK analysis has zero errors/warnings (generated style infos remain). Strict
Redocly and owned formatting pass. Previous canonical paths/schemas remain
structurally identical; the future facilitator exam flag is optional without a
default or server activation.

This increment does not claim usable app screens or answer-photo feedback.
Those remain separate active work, as do external provider/legal/safety and
physical camera qualification. No paid inference or production setting changed.
