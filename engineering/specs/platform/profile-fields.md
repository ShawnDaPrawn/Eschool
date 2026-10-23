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
checkbox, textarea and file definitions and add dates/booleans. New files use the
existing authenticated file boundary rather than arbitrary remote URLs.

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
