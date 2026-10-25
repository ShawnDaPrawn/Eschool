# Houses and awards — API qualification

The v2 recognition API adds 19 operations and 41 schemas, preserving parsed
existing path and schema values. It exposes fresh-authority staff configuration,
scoped learner choices, aggregate counters, separate house-point history and
permanent award profiles, nomination windows/queues and exact UUID mutations.
Calendar days are strings. The API and services share one validation rule set.
Wrong module/path combinations return 404; modules remain off by default.

The integrated recognition and contract cohort passed 391 tests / 1,966 assertions.
A final recognition cohort passed 30 tests / 273 assertions after adding retained
history reads for current managers and a retained presented-nomination version.
Configured whole-application static analysis and Pint pass. Cases include actual
HTTP family/staff flows, wrong account/school/role/child, private query mutation
refusal, access revoked before replay, and read-only modules retaining GET access.
The existing full contract inventory covers every new route with authentication
refusals, while the recognition suite exercises positive payloads and policies.

The pinned 7.25.0 generator produced 1,410 Dart files. All 83 existing SDK tests and
four new recognition tests pass; SDK analysis has no errors or warnings (generated
style information remains). Strict OpenAPI lint passes. Existing optional Risk
school-day and progress-group term fields from the parallel stream are preserved.
Generation first exhausted temporary disk headroom during overlapping test
containers, then completed after cache recovery with no PHP container running.

The new SDK tests preserve signed point amounts and operation identities, zero
counter revisions, nullable references, school calendar dates and outside-award
profile projections. No manual generated-code edits were made.

This API increment does not establish staff/assembly browser flows, native app
entries, notification delivery or the live moment latency. Those follow separately,
as do staging/device/fluent-language/provider acceptance and production approval.
