# Authentication security patch — 8 November 2026

## Release scope

The owner requested deployment of the authentication hardening. This patch is
based on the last recorded production release, **v0.1.1**
(`84dd86225ed7ce7914172487cf1465dc57115742`), so unrelated changes on main are not
part of the release. It adds no database migration, dependency update or native
client change. The production backup and protected deployment process remain in
place. Deployment is not claimed by this source-review record.

The earlier main-branch implementation is tracked in
[PR #471](https://github.com/ShawnDaPrawn/Eschool/pull/471). Its test counts and
newer staff-invitation service are not evidence for this backport. This release
uses the password-reset flow already present in v0.1.1 for new staff accounts.

## Security changes

- Login failures and rate limits resolve email, handle, learner number and user
  ID to the same account. A failed app login cannot lock unrelated accounts.
- Disabled users cannot obtain new tokens or reuse existing authenticated panel,
  v1, v2 or private-profile access.
- Required password changes are enforced independently of client headers, and
  the panel account flag also restricts cookie-authenticated APIs. v1 retains
  its existing password-change code; v2 retains its documented error envelope.
  Password changes, sign-out and session/policy setup remain accessible.
- New teacher/staff accounts receive unknown random passwords and password-reset
  instructions. Restoring a legacy teacher retires old tokens. Parent creation
  and imports use random temporary credentials, and existing parent passwords
  are preserved. Learners retain their guardian-approved setup flow.
- Installation requires configured administrator credentials in all environments;
  reseeding preserves existing credentials, and the installer no longer displays
  passwords.
- Reset delivery failures do not reveal registered email addresses. Validation
  excludes password and setup secrets from session flash data. Password changes
  are throttled and cannot reuse the current password to clear a restriction.

## Verification

The release-specific regression suites, static checks and full CI results are
recorded in the release pull request. Tests use PHP 8.3.36, an isolated ephemeral
MySQL 8 database ending in `_test`, array mail and synthetic accounts. Existing
application containers and data are untouched. No real invitation or reset
email is sent. Raw local evidence is retained under `/tmp/eschool-auth-backport`.

This is a source review and regression exercise, not a guarantee of complete
security. The Codex Security skill was consulted in the original review, but
its supporting references and scan/report tools were unavailable; no automated
Codex Security certification is claimed. The private Build Bible was unavailable.

## Remaining operational work

- Existing predictable passwords still need a controlled reset and revocation
  process. A required change after login alone cannot stop someone who knows
  the old password from attempting to take over an account.
- Verify deployed mail delivery and password changes in the profiles actually
  used by the school. Record infrastructure/cookie/CORS settings, shared session
  and rate-limit storage, token expiry and cleanup separately from source tests.
- The existing guardian-approval rollout setting is unchanged. Staff-wide MFA,
  independent penetration testing and live device acceptance are outside this
  patch's evidence. No activation or acceptance is implied by deploying it.
