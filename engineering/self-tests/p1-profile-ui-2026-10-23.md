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
