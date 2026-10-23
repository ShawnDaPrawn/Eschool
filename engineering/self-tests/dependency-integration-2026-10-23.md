# Open dependency PR integration — 23 October 2026

Repository maintenance: integrate #48, #49, #50, #53, #55, #56, #57, #59 and #60 together, preserving every original PR head in the merge history. This is not a phase acceptance or production release record.

## Compatibility decisions

- Flutter 3.47.7 stable / Dart 3.13.5 meets Google Fonts 9's Dart ^3.13 / Flutter >=3.47 requirements. CI and deployment use the same pin. Both apps declare Dart >=3.13.0. Regenerate the API client with the pinned generator and new toolchain. Refresh lockfiles throughout the monorepo and retain the API generator's minor-version bound for build_runner.
- Google Fonts 9's text-theme helpers return material_ui's TextTheme, whereas the existing widgets use Flutter's. Apply Poppins to each Flutter TextStyle, preserving the established Material type scale and primary text theme.
- File Picker 13 returns non-null lists for multiple selection, with empty-list cancellation. Use pickFile for single uploads. Both apps use 13.1.0 rather than retaining the teacher app's beta wrapper API.
- TeX 5 requires renderer startup, separate controllers for simultaneous question/answer views, and its bundled browser bridge. Permit HTTP only to localhost / 127.0.0.1 in the learner app's release Android network policy and local networking in iOS; remote API traffic remains HTTPS. Firebase allows same-origin framing only for the bundled TeX asset path, retaining DENY on the application itself. Exercise actual rendering in the Android integration suite and an added Chrome flow.
- Image 4 requires decodeSplFileInfo and encodeUsingFormat/encodeUsingMediaType in the tenant upload service, legacy image service and resizeImage helper. Encode extensionless PHP upload temporaries using their decoded media type. JPEG output names use .jpg.
- Preserve Excel 4's collection/filter signatures and Permission 8's renamed role events. Test revocation and re-grant across two schools, including membership-cache invalidation.
- TypeScript 7 removed Node10 module resolution. Use Node16 resolution and add the existing e2e typecheck command to CI before Playwright.
- Retain html <0.15.7 because flutter_html 3.0.0 imports an API removed in html 0.15.7. This existing compatibility pin is intentional; the grouped PR's remaining upgrades are included.
- Flutter's updated outlined-button rendering changes 420 pixels (0.09%) of the shared component golden. Inspect the before/after and update the golden without relaxing the comparison.

## Regression coverage and merge gates

New checks cover extensionless PNG/JPEG image rewrites, JPEG storage/deletion, school role revocation/re-grant, preservation of typography, native Stripe sheet setup ordering and cancellation, and independent rendering/submission of mathematical exam content.

Before merging, require green combined CI and Security workflows: PHP runtime and platform requirements, complete PHP tests and static analysis, all seven Dart projects' analysis/tests, reproducible API generation, both release web builds, Android and Chrome smoke flows, panel Playwright/typecheck, OpenAPI lint, documentation build, dependency audits and whole-history secret scanning.

## Release boundary

No production deployment is requested. An actual payment-provider staging transaction and iOS device acceptance require configured staging/test-provider credentials and Apple tooling. The native-channel checkout tests and server signature/idempotency/amount/currency tests provide automated regression coverage, not evidence of a live staging transaction.
