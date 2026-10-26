# Counsellor case context verification

Implementation: existing private risk signal DTO, no new endpoint or SDK generation. Requires both current RiskCaseload and explicit clinical case grants, plus a fresh Counsellor role. Counts only the reader's own open cases on read; no clinical entries/reports are loaded and no risk observation is changed.

Verification completed locally:

- Focused cases: 5 tests / 31 assertions, including a real typed HTTP response, private panel rendering and revocation during case retrieval.
- Existing Risk API plus Wellbeing domain with the first four focused cases: 30 tests / 416 assertions passed. The five existing Risk panel cases also passed in the subsequent cohort; its new mixed API/panel fixture initially used the API guard for a panel request and was corrected to the explicit web guard before the final focused pass.
- Shared Risk widgets: 12 passed, including own-case wording and unchanged unknown-state behavior; the new case also passed at 390px.
- Selected affected PHPStan analysis and Dart analysis report no errors. Pint and diff whitespace checks pass. The attempted whole-app PHPStan process exited without a diagnostic, so whole-app static acceptance remains CI work rather than a recorded local pass.

No new endpoint, schema, SDK model or migration was needed. No real provider, production, physical-device or full-browser acceptance is claimed for this count addition.
