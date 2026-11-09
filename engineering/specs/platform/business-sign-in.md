# Separate business administration sign-in

The owner requires business/HQ administration to have a distinct login from
school Super Admins (10 November 2026). Business staff sign in at `/saas/login`;
school administrators and staff continue to use `/login`. The business page
has its own branding, form action, recovery pages and logout destination.

The business credential query requires an active user, active platform staff
identity, and active, unexpired grant with a recognized business role. School
accounts cannot use it. School sign-in and school password recovery exclude
platform identities, including revoked identities. Responses to failed sign-in
and recovery do not disclose whether an address belongs to a business account.

A successful business sign-in starts a fresh session, records the authenticated
business user ID and still requires the existing password-change and two-factor
checks. Every HQ request requires that business sign-in proof in addition to its
existing staff/grant/ability checks. Old shared-login sessions, remembered school
sessions and a proof for another user cannot enter HQ. Login/logout events clear
the proof; password recovery requires signing in again and never bypasses MFA.

This uses the application's existing user records and Laravel session storage.
A business sign-in replaces the current browser identity; it does not create
simultaneous school and business sessions. A separate hostname or cookie store
is not required for the distinct login routes. No school roles, platform grants
or passwords are created or changed by this rollout.

## Routes

| Purpose | Business | School |
| --- | --- | --- |
| Sign in (GET/POST) | `/saas/login` | `/login` |
| Sign out (POST) | `/saas/logout` | `/logout` |
| Request reset (GET) | `/saas/password/reset` | `/password/reset` |
| Send reset link (POST) | `/saas/password/email` | `/password/email` |
| Open reset link (GET) | `/saas/password/reset/{token}` | `/password/reset/{token}` |
| Set reset password (POST) | `/saas/password/reset` | `/password/reset` |

Business invitations and expired-session/MFA-reset redirects return to business
sign-in. Reset notifications choose the account's corresponding reset URL using
the configured application origin. Shared CSRF protection, login throttles and
account-level failed-login limits remain in force. Recovery delivery still
depends on a working mail provider; SES setup is currently paused.

## Verification

`BusinessSignInTest` covers distinct pages, cross-account-type refusal for both
schools, stale/wrong-user session proof, revoked/expired/inactive/unknown-role
accounts, separate recovery URLs and reset rejection at the wrong entry point.
The existing HQ, SaaS, platform role and MFA suites cover authorization and the
forced password change flow. The `saas-panel.spec.ts` browser flow uses the new
login and exercises password change, MFA, HQ work and revocation at three widths.

Local verification (10 November 2026):

- 117 backend tests passed across the affected suites and targeted reruns:
  business sign-in, SaaS panel, platform roles/MFA, HQ home/school accounts,
  authentication boundaries, password policy, session concurrency, date-of-birth
  passwords, translations, routes, Bootstrap, design-token and admin-page checks.
- All 13 affected browser checks passed: business/SaaS and AI-cost controls at
  390, 820 and 1280 pixels, plus school fee planning. Coverage includes
  login/recovery accessibility and overflow checks, password change, MFA,
  school setup, school-admin sign-in and revocation. The AI-cost fixture now
  uses a distinct business account and completes MFA instead of converting a
  school finance user into platform staff.
- PHPStan, changed-file Pint, Playwright TypeScript, route cache generation and
  `git diff --check` passed. Two existing MFA audit assertions now compare JSON
  objects without depending on MySQL/MariaDB key ordering, preserving strict
  value/type checks.

The complete repository suite and remote CI have not been qualified by these
targeted runs. This source change is not a live deployment; existing business
users must sign in again through `/saas/login` after rollout. Provision the
owner as a distinct account through the existing platform invitation/grant
mechanism; do not convert a school's administrator into the business owner.
