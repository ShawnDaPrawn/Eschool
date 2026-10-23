# Configurable person profiles

Schools manage categories and field definitions for learners, parents, teachers,
facilitators and counsellors. Platform-dependent defaults stay locked against
removal/type changes; schools can order them and narrow permitted visibility.
The confirmed defaults in `engineering/phases/phase-1-questions.md` apply.
Religion is optional and hidden initially; caste is retired from the model.

Three scoped tables hold categories, definitions and per-person values. Keys,
types, options, validation, visibility/edit roles, required/locked state and
ordering are metadata. Sensitive values are encrypted and hidden from model
serialisation/audit payloads. Decryption follows field and person authorization;
exports omit protected fields unless the actor has the explicit right.

One validation/serialization service drives a Blade ProfileForm and the shared
Flutter form, and supplies import/export metadata. A category/field created in
Settings appears on the learner form, app profile and permitted export without
another code change. Types preserve legacy text, number, dropdown/radio,
checkbox, textarea and file definitions and add dates/booleans. File fields require a dedicated authenticated profile-file boundary; arbitrary
remote URLs are not accepted. That boundary is a remaining implementation item.

Core identity properties continue using their established person records. Custom
values are school-specific, including for a human with several memberships.
Policies permit self access, active authorised guardians for their learner, and
school staff within their person/field rights. Teachers require their existing
learner relationship; platform support cannot read protected values through
impersonation. Wrong-school/role/owner requests fail before loading values.

Legacy form definitions and dynamic JSON are migrated with a resumable backfill
and compatibility adapters. Protect existing v1 shapes and installed clients;
retired caste remains an empty compatibility field where v1 requires its key.
Legacy edits/imports use the same field validation and synchronize canonical
values. No sensitive plaintext duplicate remains in legacy storage after its
successful backfill. Destructive schema/data retirement is explicitly documented
for the separate production rollout, not executed in production by a safe merge.

Add authenticated v2 profile read/write contracts and generated app DTOs,
metadata-only context, the M3 categories/definitions editor, and a shared app
profile route. Validation errors retain entered values; locked/invisible fields
cannot be changed through crafted requests. Existing servers keep their profile
screens until the new endpoint is available.

Verify default locks, validation types/options, encryption/raw SQL and logs,
field visibility, wrong-school/role/owner, legacy migration/rollback and v1
snapshots. Exercise the Transport category/route scenario through the panel,
shared app and export; run Playwright, package tests and affected client flows.
Staging/owner/participant evidence remains separate and does not freeze code.

## Delivery slices

The schema and API slice adds seeded school-specific definitions, encrypted
custom values, person/field policies, partial validation, configuration APIs and
the generated client. Supported types in this slice are text, textarea, number,
date, dropdown, radio, checkbox and boolean. This API slice merged in PR #75 with all 25 checks passing. Old forms and old
data remain in place.

The editor/form slice connects the M3 categories/definitions editor, shared
learner/guardian/staff app form, metadata-only context and permitted CSV export.
Partial writes send changed keys only. Private panel forms disable browser
drafts and validation input flashing; app drafts remain in screen memory and
are cleared before changing subjects. Explicit per-request school scope keeps
guardian edits bound to the selected child without changing global selection.
Export rights use active membership roles and filter sensitive definitions
before querying or decrypting their values. CSV cells neutralise spreadsheet
formulas, and exports audit field keys only. Resumable legacy migration, compatibility writes, file
fields and retirement of old caste/religion storage follow before this feature
is complete. No legacy sensitive plaintext is claimed to have been removed.

New tables are additive. Migration rollback drops only these tables; once real
values exist, export/backup them before rollback. APP_KEY must be preserved for
profile ciphertext as for other encrypted application data. No production
migration or data retirement is performed by merging code.
