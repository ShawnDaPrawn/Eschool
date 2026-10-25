# Wellbeing HTTP and typed SDK verification — 25 October 2026

This increment adds 31 real v2 operations over the encrypted incident/case
services: anonymous public configuration/submission/follow-up/replies, explicit
named identification and own reporting, professional triage/casework/calendar,
and separate category/team/case-assignment metadata administration. It does not
yet add the public HTML reporting flow, restricted panel/app screens, attachments
or notices. The wellbeing module stays disabled by default.

The anonymous routes are stateless even when called with a portal-origin cookie;
no authenticated actor is consulted or session cookie issued. Secret codes appear
only in POST bodies; immutable write acknowledgments contain no private code,
report body or reporter identity. Identified reads require the freshly authorized
named reporter, and code-only access stops after consented identification. Fresh
school/account/role/module, separate dated triage/case grants and shared sensitive
record exclusions precede validation and private data loading. Password-change
requirements also apply to retained Teacher tokens. Private responses and errors
forbid caching.

Management choices are named and paginated within the selected school; another
school's professionals cannot crowd out its own choices or affect pagination.
Managers receive assignment metadata and no clinical bodies. No Teacher record
is required for a legitimate counsellor. Source/domain validation rules are shared
with FormRequests. Public anti-abuse counters use keyed short-lived values
separate from report records.

Actual results:

- Complete wellbeing domain/migrations, canonical route/contracts and controller
  guard cohort: 340 tests / 1,809 assertions passed.
- Expanded final actual HTTP cohort: 7 tests / 393 assertions, including all 27
  authenticated operations refusing the wrong school before validation, all
  management/clinical operations refusing nonclinical roles, own-reporter scope,
  platform/password/assignment withdrawal and 31 foreign professional choices.
- Full Larastan: zero errors; changed PHP formatted with Pint; strict OpenAPI
  lint and whitespace checks pass.
- Pinned SDK generation: 1,194 Dart files. An actual generated transport test
  verifies the anonymous code is absent from URLs/query/auth requirements and
  sent only in a POST body. Correction UUID/reference and private receipt tests
  cover meaningful wire boundaries. Final SDK count is recorded below.

The calendar-date wire test exposed a real generator limitation: format:date
serialized assignments as timestamps, which strict YYYY-MM-DD rules refuse.
The new owned calendar fields now use constrained strings, preserving exact date
wire values; server date validation remains strict. Existing canonical paths and
schemas compare equal to the combined preceding contract, and every owned
wellbeing definition matches its canonical counterpart.

Physical devices, live school response processes, ingress/body-log privacy,
clinical-language review, media scanning, staging, acceptance and production/store
release are separate checks. None is inferred from these synthetic tests.

Final SDK verification: all 74 tests pass. Analysis has zero errors/warnings and
657 generated informational lints. The date regression now passes with exact
YYYY-MM-DD input/output. Final owned/canonical definition comparison passes.

Transport follow-up: all private-code inputs explicitly reject a `secret` query
parameter, even when a valid body also exists. Write validation reads the body,
never merged query input. The final seven actual HTTP tests pass 401 assertions
and full Larastan/Pint pass after this additional body-only enforcement.
