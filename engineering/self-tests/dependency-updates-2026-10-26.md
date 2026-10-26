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

Remaining: upgraded Flutter analysis/tests, private image/HTML/browser
qualification, exact published-head CI and safe main merge. The local installer
initially followed shared vendor namespace links. Those central packages were
reinstalled at their original pinned versions and route-loading was verified;
all upgraded namespaces are now private copies. Source files were unaffected.
