# Consultation API and client contract

The consultation domain now has 17 additive v2 operations for access metadata,
scoped listing, named audience choices, authoring, frozen configuration,
configuration history, participation, closing/cancellation, aggregate results and
paged individual responses. The module remains disabled by default. An optional
facilitator feature flag reports current module/role availability; it grants no
record access.

Every bound request resolves the current account, school, role and source before
validation. Writes accept body fields only. Configuration changes use existing
revisioned operation receipts; votes deliberately have no actor-linked operation
receipt. The original stored year controls updates even if the submitted body
names the current year. Anonymous response projections expose neither respondent
identity, response ID nor timestamp. Aggregate results omit free-text answers;
individual responses require management authority after closure. Lists authorize
audience metadata before loading titles/instructions or counting a visible page.
Custom audience choices respect existing ownership permissions.

Private response middleware is shared with the wellbeing boundary and registered
before authentication in Laravel's priority list, including inherited wellbeing
middleware. This was prompted by a real failing test: authentication correctly
returned 401 but bypassed the route wrapper and lacked `no-store`.

The first run passed 31 tests and failed all 17 authentication-header cases
(911 assertions). After the priority fix, the complete corrected cohort passed
469 tests / 3,019 assertions: all 23 consultation HTTP cases, 17 domain/migration
cases, ten existing wellbeing API cases, eight private staff inbox cases, the
complete OpenAPI route/scenario matrix and controller-response checks. Whole-app
Larastan and affected PHP Pint passed. The middleware regressions cover existing
wellbeing and mastery GET/write routes, including their pre-auth refusals.
A legitimately authorized manager in the other school cannot read configuration,
history or mutate a bound consultation; current-year request fields cannot edit
an original historical-year draft.

This slice also includes Phase3's reviewed private staff inbox validation-envelope
fix: invalid pagination returns the typed private JSON envelope even with an HTML
Accept header and stores no fields in session flash. Both new inbox regressions
passed in the same corrected cohort. This depends on the shared middleware fix.

Strict duplicate-key YAML parsing, existing-path/schema semantic preservation and
owned consultation fragment equality passed. Redocly strict lint passed before
client generation. SDK generation and round-trip evidence is recorded below.

Panel/shared-app surfaces, scheduling delivery, actual live transport and deployed
acceptance remain subsequent implementation. This API slice does not claim those
flows or any production, hardware, app-store or external-provider qualification.

The pinned 7.25.0 generator produced 1,645 files; a complete second generation was
byte-identical. All 102 SDK tests passed, including four new protocol cases for
anonymous null identity/no returned response ID or timestamp, aggregate counts
without texts, vote requests without actor/receipt fields and omission of the
optional consultation feature in older profiles. Generated-source analysis found
zero errors/warnings and 903 generated naming/style information diagnostics.
