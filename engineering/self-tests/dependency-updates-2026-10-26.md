# Dependency compatibility integration — 26 October 2026

All ten original PR heads (#261–#270) are retained in the integration history.
Actions, Composer patch releases, debugbar 4, Stripe 22, icons 2, shimmer 4, cached
network image 4 and compatible Dart patch updates are being qualified together.

The proposed PHP 8.5 image is retargeted to the repository's qualified 8.3.36
runtime. Existing CI explicitly requires 8.3, so the updater now keeps PHP patch
releases on that line. The removed html < 0.15.7 bound is restored: flutter_html 3
still calls the private matches() API removed by html 0.15.7. The exception is
scoped to that incompatible dependency. Both apps' direct image/shimmer bounds
are aligned with the shared core, and consumer lockfiles are resolved together.

Stripe's selected API version is 2026-09-30.endive, verified from the public
https://docs.stripe.com/api/versioning.md and the locked stripe-php 22 API-version
source. Its 18-to-22 changelog was reviewed for the used PaymentIntent and signed
webhook paths; this integration does not send the removed payment_method_types
parameter. The service pins that reviewed version explicitly. No Stripe account
or live webhook configuration is changed. The connected documentation tool
required reauthentication; the official public sources were read directly.

Executed locally against the upgraded private dependencies:
- Composer install, validate and locked audit pass; zero advisories/abandoned.
- Explicit SDK create/retrieve protocol: 1 test / 17 assertions, no network.
- Payment/webhook, global contract and counsellor cohort: 560 tests / 2,533 assertions.
- Full upgraded PHPStan: zero errors. Full Pint and Actionlint pass.
- Both app and shared-consumer dependency resolution succeeds; the Family
  enforced-lockfile check also passes.

Additional upgraded-dependency qualification completed locally:
- Both full app analyses: zero errors and warnings. Enforced lockfiles pass for
  both apps, core, people and academics.
- Shared peer/Pathway/chat cohort: 50 passing tests; core: 119; people: 21.
- Actual private PNG/PDF profile Chrome flow passes. Actual Student, Parent,
  classic Teacher and dedicated Facilitator peer tutoring Chrome journeys pass.
  The first staff lazy-list target needed bounded scrolling; the corrected
  browser helper retains all workflow assertions.
- The peer uncertain-response regression passes with a typed transport fixture;
  original request identifiers and payloads remain unchanged on retry.
- Previously combined assistance/chat/API/payment native cohort: 651 tests /
  3,330 assertions. New panel/notices/offline-domain integration is being checked
  against the same private dependency set.

The first combined PHPStan attempt at 512 MiB exhausted its worker memory;
the full serial 1 GiB rerun passes with zero errors. Merged Family assistance/chat
notice actions pass 22 tests; classic Teacher and dedicated Facilitator chat
notices pass 13 tests. Selected Family analysis has no issues; selected staff
analysis has zero errors/warnings (six existing style infos). Playwright
TypeScript, Actionlint and the complete public-delta secret scan also pass.

The first published integration CI found one unused peer-test import. Removing
the redundant import makes the complete academics analysis pass with zero
errors/warnings (260 style infos); no analyzer rule or assertion was changed.
The merged backend/contract/tenancy cohort passes 1,372 tests / 10,464
assertions. Full Pint passes. The additional Foundation/offline/global-route
cohort passes 634 tests / 3,486 assertions against the upgraded dependencies.
Full combined PHPStan (serial 1 GiB), Pint and Playwright TypeScript pass after
that integration. Exact published-head CI and safe main merge remain.
Qualified Foundation (#295) and delegated offline reader/SDK (#296) are retained
in this integration, including all seven Foundation and five offline browser
journeys recorded in their own evidence. Encoding remains a separate work item;
no physical hardware or production acceptance is claimed.

The local installer
initially followed shared vendor namespace links. Those central packages were
reinstalled at their original pinned versions and route-loading was verified;
all upgraded namespaces are now private copies. Source files were unaffected.

CI also exposed 50 new chat keys absent from six catalogues in each app, plus
new Guardians/Learners fallbacks inconsistent with the existing Afrikaans/isiZulu
glossary. The integration adds English fallbacks only for missing keys and
applies the established glossary; existing translated values are preserved.
The original catalogue equality, placeholder and glossary tests remain intact.

All five original translation tests pass independently in each app after the
catalogue repair. The existing Parent activity cancellation-refresh test also
passes after its fixture provides the real Parent AuthCubit boundary required
by the chat-aware entry route; its private-erasure and exact account/header
assertions are retained.
