# Private profile files — local verification

23 October 2026. Scope: encrypted file fields in the existing protected profile
form, with panel/shared-app upload and authenticated previews. External staging,
real devices, owner acceptance and production release remain pending.

## Automated evidence

- Final focused backend run: **75 passed, 785 assertions** across private files,
  OpenAPI contracts, account security and the migration rehearsal. It covers
  PNG/PDF, own/guardian/admin access, school/role/person refusals, unverified
  guardians, required fields, stale/forged references, encrypted metadata/blob/
  audit, sensitive support/export restrictions, failed-write cleanup, native
  bearer auth, anonymous refusal and protected CORS.
- Initial full backend run: **936 passed, 3 failed**. A local installed marker
  caused two installer assertions; it was removed. The migration rehearsal
  needed the new file table in its reverse/forward list. All affected suites
  passed in the final run above. Exact-head CI supplies the final full run.
- Shared people package: **67 tests passed**; expanded profile cases: **14 passed**
  on phone and desktop, including partial saves, stale pickers, upload failures,
  refresh failure after acknowledgement and preserving unrelated drafts.
- Shared core: **106 tests passed**; authenticated byte requests preserve school
  selection/auth and handle 401 through the existing sign-out path.
- Generated API package: **10 tests passed**. SDK regenerated from OpenAPI.
- Panel Playwright: **5 flows passed**, including both 1024/1280 layouts,
  accessibility, upload → cookie-authenticated PDF → save another draft → clear
  → old-link refusal, plus teacher refusal and existing profile/export flows.
- Real Chrome shared profile smoke passed with authenticated image and PDF
  previews, explicit school scope and a retained unrelated draft. Native CI is
  still required, including the shared PDF renderer's byte input.
- Strict OpenAPI lint, Larastan, Pint (767 PHP files), TypeScript, documentation
  build and config/route/view caches pass. App analysis has no errors or
  warnings; existing informational seed lints remain.

The browser test server uses array mail, no live credentials and a separate demo
DB. Development debug-bar accessibility findings were removed by disabling the
debug toolbar, matching CI. No accessibility allowlist exception was added.

## Data and delivery

Run the additive migration only in an authorised deployment. Ciphertext uses the
existing private storage/app mount, outside the public link. Retain the database,
private blobs and APP_KEY together before any rollback. The infrastructure source
requires uniform bucket access/public-access prevention; verify actual staging
storage/permissions separately. Upload rollback removes its new ciphertext;
replaced/unreferenced ciphertext stays private pending the general retention job.

Legacy public files, dynamic-field migration, caste/religion storage retirement,
general retention, operational staging and acceptance remain separate work.
