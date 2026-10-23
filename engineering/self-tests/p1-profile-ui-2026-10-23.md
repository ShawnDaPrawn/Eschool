# Profile editor and form verification — 23 October 2026

[Specification](../specs/platform/profile-fields.md). This slice adds the editor,
shared app form, metadata-only context and permitted CSV export to PR #75's API.
Legacy backfill, compatibility writes, secure file fields and storage retirement
remain separate implementation work.

The shared people package passes 60 tests. The new cases cover explicit school
headers while retaining auth, decimal/leading-zero text and false/clear values,
old-server availability, partial saves with unrelated required fields missing,
locked identity, failed-save recovery, subject selection, discard cancellation,
private-value clearing before a request and stale-response exclusion. Phone
and desktop layouts run with the apps' theme and labels.

Backend tests cover the Transport category/field → person form → partial save →
export flow, wrong-school/role/owner, no private validation session input,
formula neutralisation, metadata-only context, and filtering protected fields
before decrypting corrupt ciphertext. Expired admin roles cannot grant a
sensitive export through an active teacher membership.

Full backend regression, app checks, browser/accessibility and Chrome smoke
results are recorded below as they complete. No real mail, push, cloud resource,
production data migration, staging or participant acceptance is claimed.

Both app analyses pass with existing informational lints only. Learner/parent
unit tests pass 43 tests; teacher tests pass 26. The new real-Chrome guardian
profile smoke passes, checking the chosen child's school header and a partial
write from Settings through save and refresh. The documentation build and
Playwright TypeScript check pass. Browser testing also found an unset school
date-format setting breaking the learner list; the list now uses a fallback.

All three new panel Playwright flows pass: the complete Transport path at 1024
and 1280 pixels, and teacher self access with configuration refusal. Both new
pages pass serious/critical axe checks without an allowlist entry, have no
horizontal overflow, and the complete successful flow has no console/HTTP
errors. Browser storage contains no typed profile value. Shared core tests
pass 105 tests. Larastan passes after the list fallback fix.

The first full backend run exposed old navigation/search expectations that
excluded all teacher Settings entries. They now permit only the new self
profile destination while continuing to exclude school configuration. Both
panel navigation labels are present in English and Arabic.

The full local backend run passed 900 tests and reported four obsolete
navigation/search assertions. After updating those expectations and the Arabic
navigation labels, the focused navigation/search/profile-panel run passes
25 tests with 579 assertions, including the new missing-date-setting and
expired-admin export regressions. Exact-head CI supplies the final full-suite
result. The first PR PHP job also identified two formatting omissions in the
context registry and web routes; both are formatted before the final run.

Review found global-person export audits lacked a school. Profile updates and
exports now supply the authenticated school explicitly to Audit. Nine panel
regressions pass with 45 assertions, including exporting the same global person
at two schools and recording separate school IDs. Existing audit subject IDs
and field-key-only payloads remain intact. All 753 PHP files pass Pint, and
configuration, route and Blade caches build and clear successfully.

The full PR backend job passed 906 tests with 11,667 assertions. The browser
sweep's remaining old teacher-menu assumption is updated too: the teacher sees
My profile, but no school configuration links, and all ten admin destinations
still refuse access. The focused teacher browser project passes 11 checks.
