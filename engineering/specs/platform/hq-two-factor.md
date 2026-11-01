# Two-factor sign-in for platform staff

Action Plan SA.2. Owner role: Backend and Admin UX (HQ).

HQ (`/saas`) can suspend schools, change what they pay for and read every school's counts. A password alone is not enough for that. Every platform staff member now needs a code from an authenticator app on their phone as well, and HQ does not open without it. School accounts are not affected.

## User stories

- As a newly invited staff member, after choosing my password and signing in, I am taken straight to setting up my authenticator app, and I see my recovery codes once.
- As platform staff, each time I sign in I type the code my app shows before any HQ page opens; if my phone is lost, a recovery code lets me in once.
- As an Owner, when someone loses their phone, I reset their two-factor sign-in from the staff page with a reason, and they set it up again at their next sign-in.
- As the only Owner who lost their phone, the operator console resets mine (break-glass), with a reason, recorded in the audit log.
- As a school, nothing changes for my staff, teachers, learners or parents.

## Decisions (lead, 31 October 2026)

- **Authenticator apps only (TOTP, RFC 6238).** Passkeys are a later step (below). Any standard app works: Google Authenticator, Microsoft Authenticator, 1Password and others.
- **No new packages.** TOTP is implemented in `App\Services\Platform\Saas\Totp`: HMAC-SHA1, a 30-second step, 6 digits, one step either side of now for clock drift, constant-time comparison (`hash_equals`). Secrets are 160 random bits (`random_bytes`) in Base32. It is tested against the RFC 6238 SHA1 and RFC 4226 vectors. The QR code is drawn as inline SVG with `bacon/bacon-qr-code`, which was already a dependency.
- **Recovery codes are stored as HMAC-SHA256 with a pepper derived from the app key, not `Hash::make`.** Each code is 80 random bits, so a slow password hash adds nothing against guessing. It would only make every challenge check ten bcrypt hashes. Because the hash is keyed, a copy of the database alone cannot test a guess. Codes made before a key rotation keep working while the old key is in `APP_PREVIOUS_KEYS`.

## Rules

- **Who.** Every active platform staff identity with a current grant, whatever their role. A school account (a school Super Admin included) gets 403 on every two-factor page, exactly as on the rest of `/saas`, so the answer reveals nothing.
- **Setup first.** A staff member without a confirmed app who opens any `/saas` page (or sends any `/saas` form) is sent to `/saas/two-factor`. The page shows the QR code (an `otpauth://totp/NavigateEd%20HQ:{email}` address) and the setup key for typing in by hand. Refreshing the page keeps the same pending secret. The account's **current password and a code from the app** confirm it. The password is asked for at every setup, not only after a reset, so a session alone (a stolen one, or one left open) can never attach someone else's authenticator. A wrong password counts towards the rate limit and is audited (`during: enrolment_password`). The ten recovery codes are then shown on that response only, and their plain text is never stored. Setup needs a settled password: someone who must still change a default password changes it first, so a known default password cannot claim the account's second factor.
- **Challenge per session.** With an app set up, each new browser session passes `/saas/two-factor/challenge` before any `/saas` page. The page asked for is kept and opened afterwards (only a `/saas` address). Either a 6-digit code or a recovery code works (spaces, dashes and lower case are fine).
- **A code works once.** The newest accepted time step is stored (`last_used_step`). That step, or any before it, is refused, so a code seen over someone's shoulder cannot be replayed. A recovery code is removed when it is used.
- **Rate limit.** Five refused codes a minute per staff member and address; then even a right code waits (the message says how long). The routes also carry `throttle:20,1`.
- **Passed state.** It lives in the session (`platform_two_factor`), bound to the session id, the account's current password and the current enrolment, with an app-key HMAC. The session id is regenerated when the challenge is passed. Signing in or out forgets it (`ForgetPlatformTwoFactor` on `Login` and `Logout`). A password change, a reset or a re-enrolment invalidates it at once, and another session with a copy of the data does not pass.
- **Everywhere in HQ.** `platform.staff` (`EnsurePlatformStaff`) checks it on every `/saas` group. `platform.can` (`EnsurePlatformAbility`) checks it again, so no ability opens without it even if a group forgot the middleware. JSON callers get 403 instead of a redirect. The platform bar shows no HQ links until it is passed. The operator's own password page (`platform.staff:password`) stays open before a forced password change. Once an app is set up, though, it also needs the challenge, which is allowed during a forced change. The two-factor pages themselves use `platform.staff:two-factor`.
- **Sign-out on reset, whatever the session driver.** `platform_staff_identities.two_factor_generation` starts at 0, and every reset raises it. Each sign-in stamps the session with the account's current generation (`ForgetPlatformTwoFactor` on `Login`). Nothing in `/saas` can raise a session's stamp. A session with no stamp counts as generation 0. `EnsurePlatformStaff` signs out a session whose stamp is older, for every purpose (HQ pages, the own password and the two-factor setup and challenge pages): it logs the session out, invalidates it and sends the person to the sign-in page (JSON: 401). Deleting `sessions` rows alone would not be enough: with the file, redis or cookie driver those sessions would stay signed in. That gap was Codex finding 4429815006.
- **Reset from HQ.** On `/saas/staff` an Owner (`staff.manage`) resets another staff member's two-factor sign-in with a reason (10 to 500 characters). Their app and recovery codes are removed and their generation is raised, which signs out every session of theirs. Database-stored `sessions` rows are also deleted at once. They then sign in with their password and set up the app again. Nobody resets their own here (403). Resetting someone with nothing set up is 409, and someone who is not platform staff is 404.
- **Break-glass.** `php artisan platform:reset-two-factor {email}` on the operator console only asks for a reason and a confirmation, then does the same reset for anyone, an Owner included.
- **Audit (SA.3).** All of these show in the HQ audit log against the staff member's platform identity, with no school: `platform_two_factor_enrolled`, `platform_two_factor_passed` (`method: app`), `platform_two_factor_failed` (`during: enrolment` or `challenge`), `platform_two_factor_recovery_code_used` (codes left), `platform_two_factor_reset` (`via: hq` with `changed_by`, or `via: console`, and the reason). No audit row holds a code or the secret.
- **The secret stays secret.** It is shown only on the setup page, to the person setting it up, until they confirm. It is stored with Laravel's `encrypted` cast and hidden from arrays and JSON. The model is not `Auditable`. A refused code is never flashed back into the session (`dontFlash`). It never appears in another response, the audit log, its export or the application log.
- **Invitations.** The invitation page and its confirmation say that an authenticator app comes next. After choosing a password the person signs in and lands on setup (SA.1's flow, unchanged otherwise).

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `platform_staff_two_factor` | global | one row per staff member: the encrypted secret, when it was confirmed (null while pending), the last used time step, and the hashes of unused recovery codes |
| `platform_staff_identities.two_factor_generation` | global (existing table) | how many times the staff member's two-factor sign-in has been reset; sessions stamped with an older value are signed out |

The migrations' `down` refuses to drop the table while it holds rows, or the column once any reset is recorded.

## Endpoints (HQ panel, English only)

| Method | Path | Name | Middleware |
| --- | --- | --- | --- |
| GET | `/saas/two-factor` | `saas.two-factor.enrol` | `platform.staff:two-factor` |
| POST | `/saas/two-factor` | `saas.two-factor.confirm` | `platform.staff:two-factor`, `throttle:20,1` |
| GET | `/saas/two-factor/challenge` | `saas.two-factor.challenge` | `platform.staff:two-factor` |
| POST | `/saas/two-factor/challenge` | `saas.two-factor.verify` | `platform.staff:two-factor`, `throttle:20,1` |
| POST | `/saas/staff/{user}/two-factor/reset` | `saas.staff.two-factor.reset` | `platform.staff`, `platform.can:staff.manage` |

The code forms use `TwoFactorCodeRequest`. The reset uses `ManagePlatformStaffRequest` (`staff.manage`, a reason). `PlatformTwoFactor` checks the permission, the person and the reason again. No API or OpenAPI change.

## Acceptance tests

`Unit/Platform/TotpTest` and `Platform/PlatformTwoFactorTest`:

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_totp_matches_the_rfc_6238_sha1_vectors`, `test_hotp_matches_the_rfc_4226_vectors`, `test_base32_round_trips_and_matches_rfc_4648`, `test_verify_accepts_one_step_either_side_and_refuses_reuse`, `test_the_setup_address_names_the_issuer_account_and_parameters` | the published vectors | codes are made and checked | they match; one step either side works, two do not; a used step never works again |
| `test_a_staff_member_without_two_factor_sets_it_up_before_any_hq_page_and_sees_recovery_codes_once` | an Owner who has just signed in | they open HQ pages and send a form; set up with a wrong code, a wrong or missing password, then both right | redirected to setup, nothing written (JSON: 403); the same secret on reload; the wrong code and the wrong password refused and audited; ten codes shown once, only hashes stored; HQ opens |
| `test_each_new_session_passes_the_challenge_before_any_hq_page` | Support with an app | they sign in; pass; use another session id; sign out and in; change their password | challenged first and taken to the page asked for; challenged again in each of the three cases |
| `test_an_operator_who_must_change_their_password_passes_the_challenge_first` | an operator with an app who must change their password | they sign in | the password page needs the challenge first (the JSON change is 403 until then) |
| `test_a_wrong_or_reused_code_is_refused_and_attempts_are_rate_limited` | Finance with an app | wrong codes, a used code, an earlier code, then too many | each refused and audited; after five a right code waits; a minute later it works |
| `test_a_recovery_code_works_once` | a staff member through setup | they use a recovery code twice, then another in lower case | in once, nine left, audited; refused the second time; the other works |
| `test_an_owner_resets_another_staff_members_two_factor_with_a_reason_but_not_their_own` | an Owner, Support with an app and a session | reset without a reason; with one; again; their own; a school account | refused; removed, sessions ended, audited with the reason and shown in the audit log; 409; 403 with nothing changed; 404; Support sets it up again at the next sign-in |
| `test_a_staff_member_who_is_not_an_owner_cannot_reset_two_factor` | Operations, Support, Finance, Content, Sales | they reset Support's | 403; the app stays; no audit row |
| `test_the_console_break_glass_resets_an_owners_own_two_factor_with_a_reason` | the only Owner | the command with an unknown address, a school account, a short reason, a declined confirmation, then properly | refused four times with nothing changed; then reset, audited as the console with the reason |
| `test_a_session_from_before_a_reset_signs_in_again_whatever_the_session_driver` | the file session driver; Support signed in and past the challenge | an Owner resets them (then, for another person, the console does); the old session opens setup, sends the setup form; other stale sessions open the own password page and an HQ page as JSON | the generation rises; each stale session is signed out (sign-in page; JSON 401) and nothing is set up; a fresh sign-in with the password sets the app up again |
| `test_school_accounts_are_unaffected_and_reach_none_of_the_new_pages` | a teacher at School A, the Super Admin, a teacher at School B | they sign in and use the panel; try every new page and the reset | the panel works with no challenge; 403 everywhere; nothing written |
| `test_the_secret_and_recovery_codes_never_appear_in_any_later_response_or_log` | an Owner going through setup and a challenge | every later page, the audit log and its export, the stored row, the log | neither the secret nor a code appears; the stored secret is ciphertext |

The existing HQ suites (`SaasPanelTest`, `PlatformRolesTest`, `HqAuditLogTest`, `HqIncidentsTest`, `HqOnboardingTest`, `HqSchoolAccountsTest`, `ProductCatalogueTest`, `LuritsOfficeTest`) sign in with `Tests\Concerns\PassesPlatformTwoFactor::actingAsHq()`. This gives the staff member an app and marks the test's session as passed, the same state the challenge leaves; the middleware is not weakened. `SaasPanelTest` requires `platform.staff:two-factor` on the two-factor pages, `platform.staff` plus an ability everywhere else, and counts 41 `/saas` routes. `PlatformRolesTest` checks that an invited person's sign-in leads to setup.

## Not in this step

- **Passkeys (WebAuthn).** The plan allows "authenticator app or passkey". Passkeys need a WebAuthn library (none is in vendor, and Composer is unavailable) and belong with the pending modern sign-in work.
- Staff regenerating their own recovery codes, or replacing their own app, without an Owner. Today an Owner reset does both.
- Remembering a trusted device for some days. Every new session is challenged.
- Two-factor for school accounts.
- A lockout across addresses beyond the per-address minute limit; failed codes are audited and visible in the HQ audit log.

### Support-session sign-in and timezones

Support-session routes validate the approved grant and then reuse the platform staff sign-in guard before binding the school. A session that has not passed the second factor is challenged and records no school page view.

The session proof uses the stored confirmation marker for the enrolment. Reading the same enrolment in a different school timezone cannot change the proof. The support/MFA regression covers an unverified session, a passed session, and the Johannesburg/Auckland boundaries.
