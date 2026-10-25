# Shared private staff inbox API

- Forty backend tests pass with 449 assertions across the new inbox, actual wellbeing notices and existing facilitator/private scope/delivery suites. Coverage includes wrong owner, role, school, account replacement, platform identity, source withdrawal, read-only feature modules, SQL pagination/count filtering, exact type isolation and generic metadata without stored message selection.
- The real wellbeing regression reads one notice through the existing inbox and another through the shared inbox; both reflect the same permitted intent markers. Revoked team membership removes rows and prevents later read writes. Existing reporter replies never enter the staff inbox.
- A malicious read adapter replacing the selected school is refused; its marker transaction rolls back and the original context is restored. New markers use explicit UTC storage and repeated reads preserve the first instant. Existing domain timestamps retain their original interpretation.
- Whole-app Larastan passes, as does selected Pint. The final server capability insertion has an additional focused profile/HTTP assertion and targeted static check.
- Two additive contract operations and five schemas generate 1,550 pinned Dart files. All 98 SDK tests pass; SDK analysis has zero errors/warnings, and strict Redocly lint passes. No client provider, Teacher/Firebase inbox privilege or external transport is enabled by these operations.

Native screen qualification is a separate app increment. No physical-device, paid inference, live delivery or overall Phase 3 completion is claimed.

Local evidence: private-staff-inbox-final.log, private-staff-profile-final.log, private-staff-sdk-generate.log, private-staff-sdk-tests.log, private-staff-sdk-analysis.log, private-staff-contract-lint.log.
