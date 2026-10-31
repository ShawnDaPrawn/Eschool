# AI limits and platform cost visibility

School managers use **AI settings and caps** (`/ai-settings`). Fresh active Super Admin, School Admin or Principal authority plus `ai-settings-manage` is required; platform operator identities and impersonation are excluded. Account/school form pins precede validation. Versioned updates have immutable UUID receipts and audited numeric changes. Empty limits follow server ceilings; zero blocks new usage. Pausing affects inference, while existing safeguarding review and local transcription retain their independent controls.

The gateway applies the lesser of each school override and its trusted feature ceiling for school/person/learner daily tokens and cents. The locked reservation and every in-flight configuration recheck use the same effective limits. Withdrawal during provider work withholds output and preserves compute charges. Clearing a pause cannot activate a disabled or unqualified provider. Model selection, credentials, provider qualification and prices remain trusted server configuration.

New priced inference receipts record the server quote currency (`ai.billing_currency`, initially USD). Historical receipts retain unspecified currency instead of acquiring an invented denomination. A day's positive reserved/actual charges in an unknown or different currency block new reservations until the operator resolves the quote. Local zero-cost transcription is unaffected. Operators must keep provider quote prices in the configured currency; this setting does not perform exchange-rate conversion.

The separate `/platform/ai-costs` report requires BOTH an active platform exclusion identity and an explicit unexpired cost grant. The trusted console command `platform:ai-cost-access <user_id> --hours=24` provisions that grant; `--revoke` removes it. No production grants are created automatically. Neither grant confers school settings or learner-content access. Reports show school/currency aggregates of actual and reserved cents/tokens separately, with a bounded date range and 50-row pages. They never join flags, conversations or learner content. Fresh authority is checked before and after the aggregate query and the original school context is restored. The HQ AI control centre (`/saas/ai`, `engineering/specs/platform/hq-ai.md`) links to this report for staff who hold a grant; the report and its grant are unchanged.

Private responses use no-store/no-referrer/nosniff. Both interfaces clear when hidden. Catalogues include explicit English fallback wording; fluent translation approval remains separate.

2026-10-25 qualification:

- Complete AI cohort: **61 tests, 361 assertions passed**, including 7 operations tests / 53 assertions and three new gateway regressions / 10 assertions.
- School-owned inventory: **3 tests, 833 assertions passed**.
- Full PHP static analysis: zero errors. Changed-source Pint, JavaScript syntax, TypeScript and synthetic-key secret scan passed.
- Actual Chromium: **6 tests passed in 31.5 seconds**, covering caps edits and cost-only operator reports at 390/820/1280 pixels, serious accessibility checks, overflow, console/network errors and private background erasure.

The first browser attempt identified a missing local public storage link for synthetic avatars. Running the application's storage-link setup resolved it; the error assertions remain intact. All tests used isolated synthetic databases and public demo identities. No paid inference, deployed performance, production access grant or real-model safety certification is claimed.
