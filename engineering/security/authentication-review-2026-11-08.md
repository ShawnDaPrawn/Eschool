# Authentication review — 8 November 2026

This is a manual source review and local regression exercise, not a guarantee
of complete security. The Codex Security skill was consulted, but its supporting
references, preflight and report tools were unavailable; no automated Codex
Security scan or certification is claimed.

## Scope and boundaries

Reviewed Laravel panel and v1/v2 login, password reset/change, shared account
checks, Sanctum session/token handling, school selection, staff/guardian/learner
invitations, platform two-factor boundaries, initial credentials, and shared
Flutter credential storage. Synthetic tests use a dedicated ephemeral MySQL 8
container and PHP 8.3 runner; existing application services and data are untouched.
The private Build Bible and live deployment configuration were not inspected.
The review began at repository revision `0f00678f`. Deployment preparation
integrated current main (`4866abfb`) and is tracked in
[PR #471](https://github.com/ShawnDaPrawn/Eschool/pull/471). Production deployment
is not claimed by this source-review record.

The attacker considered is an unauthenticated caller or an existing user trying
alternate login identifiers, stale tokens, omitted headers, different roles or
school IDs. Assets include school/student records, staff authority, passwords,
session cookies, bearer tokens and invitation secrets.

## Confirmed findings and changes

| Finding | Original impact | Change |
| --- | --- | --- |
| Login failure keys used email while app credentials used user ID | Ten wrong app passwords shared an empty key and locked unrelated accounts; a successful app login did not clear it. Alternate handles also had separate short-term budgets. | Canonical account keys for all identifiers, with per-account clearing and separate unknown-identifier keys. |
| Account deactivation was enforced inconsistently | A disabled parent could obtain a token; disabled accounts could reuse tokens on shared v2 routes; a disabled school administrator could still sign in. | Shared authentication checks plus checks at session/token issuance and parent/panel sign-in. |
| Required password changes trusted a client header and were missing from v2 | Omitting the header, calling v2, or using an existing panel session bypassed the restriction. A panel default-password login did not persist the API flag. | Enforce on authenticated APIs/downloads and current panel account state; preserve password-change, sign-out, session context and policy setup. Persist the flag across panel/API authentication. |
| New accounts still received predictable passwords | Older teacher, staff, parent, public-registration and import paths used birth dates; the installer created a public password and reseeding reset it. | Staff invitations, random parent credentials, configured installation credentials in every environment and preservation of existing admin passwords. No installer password display; legacy credentials exist only in test fixtures. |
| Web reset delivery errors differed for registered addresses | A mail failure could distinguish an existing address from an unknown one. | Uniform public response while retaining server-side error reporting. |
| Validation could flash nonstandard password fields | `old_password`, `new_password` and confirmation fields could be copied into session flash data. | Explicitly exclude those fields and setup/reset secrets. |

The original nine regression cases all failed before the first corrections.
Existing controls retained include bcrypt, password rules, hashed expiring reset
tokens, fixed-origin reset URLs, portal CSRF checks, session regeneration,
HttpOnly cookies, token revocation, role/school scoping, single-use invitations,
and platform operator two-factor checks. Native credential storage uses an
encrypted Hive box whose key is held in OS secure storage; web portals default
to cookie authentication. This review did not execute Flutter/device tests.

## Verification

Initial reviewed-source results: **223 tests passed with 1,783 assertions**. These are
selected security/regression suites, not the entire repository test suite.

| Check | Result |
| --- | --- |
| Authentication, installer, password policy, tokens, portal CSRF, invitations, session concurrency and tenant/role boundaries | 196 tests, 1,506 assertions; no failures, errors or skipped tests. PHP 8.3.36 / MySQL 8.0, 11m 20s. |
| `StudentApiSecurityTest`, `TeacherApiSecurityTest`, `ParentApiSecurityTest` (including real HTTP requests) | 27 tests, 277 assertions; passed, 16m 22s. |
| PHP syntax | All 36 changed PHP/Blade files passed `php -l`. |
| Laravel Pint | `vendor/bin/pint --test --dirty` passed for 35 files. |
| Documentation | `npm run build` passed; existing Docusaurus deprecation/blog-directory warnings remain. |
| Translation JSON / patch whitespace | JSON parsing and `git diff --check` passed. |
| PHPStan | Ten missing-dependency class errors in three unchanged files; no errors in changed files. This is not a passing full static-analysis check. |
| Composer audit / locked dependency install | Blocked by proxy HTTP 403; no dependency-security sign-off. |

The earlier combined run exhausted the runner's 256 MB PHP limit. The complete
196-test run was repeated with `php -d memory_limit=768M vendor/bin/phpunit` and
used 267.5 MB at peak. Synthetic legacy administrator credentials are confined
to `tests/TestCase.php`; installer tests separately verify that missing
credentials are rejected in both testing and production environments.

The focused suite filter was:

```text
AuthenticationBoundaryTest|InstallerCredentialsTest|InstallationTest|AccountSecurityTest|DateOfBirthPasswordTest|PasswordPolicyTest|AppTokensTest|PortalSignInTest|TeacherStatusTest|AccessControlTest|FacilitatorAuthBoundariesTest|StaffInvitationsTest|LearnerInvitationTest|ExistingAccountInvitationsTest|GuardianInvitationTest|InvitationLifecycleTest|CorsTest|SessionConcurrencyTest|SchoolContextTest|SchoolRolesTest|SchoolScopeTest|ParentAcrossSchoolsTest|PanelSchoolSwitcherTest
```

Tests ran against dedicated `*_test` databases with array mail and isolated
storage/cache. No real invitation, reset email or other user notification was
sent. Raw local results are retained under `/tmp/eschool-auth-review`; temporary
test containers, their databases and the generated test credential file are
removed after verification.

During current-main integration, contract tests exposed that v2 must use its
documented `unauthenticated` and `forbidden` error codes. Shared account checks
now use the v2 error renderer while preserving the v1 response codes. New
regressions also cover vendor sign-out and pool sign-out/consent withdrawal
during a required password change. The contract-test helper normalizes the
equivalent OpenAPI 3.1 null-only type spelling for the installed validator;
positive and negative regression cases preserve strict payload validation.
The final focused checks passed: 35 tests / 874 assertions. The wider
current-main regression and full CI results are recorded separately in the
pull request before release.

## Release requirements and remaining limits

- No production deployment, password rotation or user notification was performed.
  Reset existing predictable passwords using the existing invitation/reset tools
  before treating deployed authentication as remediated. A forced change after
  login alone cannot protect an account from someone who knows its old password.
- Verify mail delivery and the password-change screen in each installed app/web
  profile in staging. Older versions no longer bypass the required change by
  omitting a header. Login identity data, session context and policy setup remain
  accessible during the restricted session so users can navigate to settings.
- Verify deployed HTTPS, Secure/HttpOnly cookies, stateful/CORS host lists,
  trusted proxies, shared rate-limit storage, session storage, application keys,
  token expiration and cleanup. Local tests cannot establish their live values.
- Install the locked PHP dependencies and rerun the dependency audit and static
  analysis before release. The supplied vendor directory differs from the lock
  file. Package/advisory downloads returned proxy HTTP 403, so dependency
  vulnerabilities could not be assessed. PHPStan reports ten missing-class
  errors for BaconQrCode and Twilio in unchanged files, including the platform
  MFA QR renderer; those features also need verification with complete dependencies.
- Verify guardian approval enforcement and outstanding consent before learner
  activation. The existing `LEARNER_APPROVAL_ENFORCED` rollout setting defaults
  to false and was not silently enabled for existing schools in this patch.
- Platform operators have two-factor enforcement; universal MFA for school staff
  is not implemented by this change. Independent penetration testing, operational
  monitoring, infrastructure review and native-device testing remain outside
  the evidence collected here.
