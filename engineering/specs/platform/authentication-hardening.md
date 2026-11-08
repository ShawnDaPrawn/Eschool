# Authentication boundary hardening

Maintenance and deployment requested on 8 November 2026. This release is based
on production v0.1.1 and contains only authentication changes, with no schema
migrations or unrelated feature updates.

- Resolve login throttles to a stable account identity for email, handle,
  learner number and numeric user ID. A failure or successful login must not
  affect another account's lockout counter.
- Disabled users receive no new app/portal sessions and cannot reuse existing
  authenticated panel, v1, v2 or private-profile access.
- Enforce required password changes on the server, independently of client
  headers. Preserve the existing response code and password-change/sign-out
  endpoints. Allow session context and policy setup for existing app navigation.
  A panel login must persist the flag for cookie-authenticated API requests too.
- Preserve v1 response fields; intentionally return 401 for disabled accounts
  and 403 `PASSWORD_CHANGE_REQUIRED` for restricted v1 accounts. API v2 retains
  its documented `unauthenticated` / `forbidden` error codes. Older clients
  must complete the change through their existing password settings.
- Give new teacher/staff accounts unknown random passwords and direct them to
  the existing single-use email password reset flow. Generate random temporary passwords for remaining legacy parent
  creation/imports and flag them for change. Existing parent credentials are
  not overwritten during import or application acceptance.
- Require configured initial administrator credentials in every environment;
  never display their password or restore a public password during reseeding.
- Password validation must not flash credentials. Mail delivery errors must
  not disclose whether a reset address exists. Password changes are throttled
  and cannot retain the same password while clearing a required change.

Verification covers positive sign-in/change/invitation paths, revoked accounts,
header omission, v1/v2 access, cross-account rate limits, role/school boundaries,
portal CSRF/session behavior and single-use invitations. See the accompanying
[review record](../../security/authentication-review-2026-11-08.md) for evidence,
environment limitations and release follow-up.
