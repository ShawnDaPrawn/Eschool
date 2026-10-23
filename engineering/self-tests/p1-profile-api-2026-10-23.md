# Configurable profile API verification — 23 October 2026

[Specification](../specs/platform/profile-fields.md). This record covers the
additive schema, service and typed API slice; the editor, app form, export and
legacy migration are still being built.

The first focused run passed 69 tests with 508 assertions, including all v2
contracts. It covers school-specific defaults and idempotent seeding, encrypted
custom storage, missing raw values in audits/idempotency responses, authorised
guardian writes, unverified/ended guardian refusal, related/unrelated teacher
access, owner/date-of-birth restrictions, cross-school cached roles and foreign
category/definition IDs. Type cases cover decimal text preservation, valid
dates, choices, duplicate-choice rejection, false booleans, explicit clear,
atomic validation and partial edits. Support impersonation excludes health
values from both reads and writes. Follow-up cases protect populated field
types, locked deletion and inconsistent validation bounds.

All 10 generated Dart client tests pass, including null write acknowledgements,
leading zeroes and the distinct false/clear representations. Strict OpenAPI
lint and Larastan pass. Full regression, formatting, documentation and final PR
checks are recorded before merge. Generated client analysis has informational
generator lints but no errors or warnings.

Only fake or array mail was used. No production data migration, cloud resource,
live message or staging acceptance is claimed. The panel/app/export Transport
flow, resumable legacy migration, file support and caste/religion storage
retirement remain implementation work.
