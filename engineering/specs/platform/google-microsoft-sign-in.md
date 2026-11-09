# Google and Microsoft sign-in

Action Plan 5.9, part 2. Part 1 (`open-standards.md`) added the OneRoster export and Open Badges. This part adds signing in with a school's Google Workspace or Microsoft (Entra ID) account, on the server side: the panel's sign-in flow, the apps' sign-in endpoint, linking, the school's settings and the API contract. App screens are a later step.

## Why

Many schools already give staff (and older learners) a Google Workspace or Microsoft 365 account. Signing in with it means one fewer password to remember, and the school's own account controls (its MFA, its leavers process) also guard NavigateEd. It must never become a way into an account that the school has not linked, or into a school that has not chosen it.

## User stories

- As a school's Super Admin, I switch on Google or Microsoft sign-in for my school, list our email domains or Microsoft tenants, and choose who may use it.
- As a teacher, signed in with my password, I link my school Google account on my linked accounts page, and from then on I press "Sign in with Google" on the login page.
- As a teacher with the teacher app, I sign in with the app's native Google or Microsoft sign-in.
- As a high school learner, where my school allows learners, I do the same in the learner app.
- As anyone, I remove a link I no longer want; my school's Super Admin can remove anyone's link at our school.
- As HQ staff, nothing changes: HQ keeps its own password and two-factor sign-in.

## Standards

- **Panel:** OpenID Connect authorization code flow with PKCE (S256), `state` and `nonce`. The start (`POST /sign-in/{provider}`, school by slug) makes a one-use challenge and redirects to the provider; the provider returns to `GET /sign-in/{provider}/callback`. The state is remembered in the browser's session (five newest attempts), so a callback this browser did not start is refused (login CSRF).
- **Apps:** the app first asks this server for a nonce (`POST /api/v2/auth/providers/{provider}/nonce`), passes it to the native Google or Microsoft sign-in, and sends the ID token it gets back with the nonce (`POST /api/v2/auth/providers/{provider}/sign-in`).
- Libraries: `firebase/php-jwt` (already in vendor) for RS256 verification and JWK parsing. No new package.

## Token checks (`IdTokenVerifier`)

1. RS256 only, with a `kid`; the signature checked against the provider's JWKS (`ProviderKeys`).
2. `aud`: a single audience, one of the configured client ids (the web client; for the apps also the Android and iOS clients).
3. `exp` and `iat` required; `exp`, `iat` and `nbf` with 60 seconds of leeway (`SIGN_IN_PROVIDER_LEEWAY_SECONDS`), and `iat` no older than 10 minutes (`SIGN_IN_PROVIDER_MAXIMUM_TOKEN_AGE_SECONDS`).
4. `nonce`: its SHA-256 must equal the one-use challenge's.
5. **Google:** `iss` is `https://accounts.google.com` (or `accounts.google.com`); `email_verified` true; the email's domain in the school's allowed domains; with "Workspace accounts only" (on by default) the `hd` claim must be present, equal the email's domain and be allowed, which refuses a consumer Google account registered with a school address.
6. **Microsoft:** `tid` a GUID in the school's allowed tenants, and `iss` exactly `https://login.microsoftonline.com/{tid}/v2.0` (never the common issuer); `oid` a GUID; `email` present with `email_verified` or `xms_edov` (the domain owner verified it) true; a guest (an `idp` other than the issuer) is refused; when the school lists domains, the email's domain must be one of them.

Every refusal is audited as `sign_in.provider_refused` with its reason and the channel, never the token, code, nonce, subject or email.

## Keys (`HttpProviderKeys`)

- Fetched over HTTPS from the configured JWKS address, no redirects, 5-second timeout.
- **Bounded:** kept between 5 minutes and 6 hours (Cache-Control `max-age` within that); a set over 64 KB or 32 keys is refused; only RSA signing keys with a `kid` are kept.
- An unknown `kid` (the provider rotated keys) fetches the set again at most once in 5 minutes.
- **Fail closed:** when no unexpired set is held and none can be fetched, sign-in answers 503 (`unavailable`). An expired set is never used.
- `ProviderKeys` and `ProviderCodes` (the token endpoint) are interfaces bound in `PlatformServiceProvider`, so tests and other environments can swap them. Tests fake the HTTP endpoints and sign tokens with RSA keys they generate.

## School control (`ProviderSettings`, `sign_in_provider_settings`)

- Off by default, per school and provider. The school's Super Admin (not in an impersonation or support session) changes them on `/sign-in-providers`. Every change is audited (`sign_in.provider_settings_updated`, choices only).
- A provider can be switched on only when this server has its client id and secret. Without them it is offered nowhere, whatever a school chose.
- Google needs at least one allowed domain; Microsoft at least one tenant id. Consumer domains (gmail.com, outlook.com and similar, `sign_in_providers.consumer_domains`) are refused.
- **Who:** every staff role by default, or the roles chosen, or none. Learners only when the school says so, in the chosen age stages (high school by default; junior can be added; early years never). Parents, facilitators and vendors never. Only current memberships count: a leaver membership does not.
- The login page shows a button only for providers the school (named by `?school=` slug, or the page's school) offers now.

## Linking (`ProviderLinks`, `sign_in_provider_links`)

- **No account creation, ever.** A provider account opens only a NavigateEd account it was linked to.
- **The person links their own account.** Signed in with their password, they type it again (the password sign-in's own lockout applies first: a locked account is refused before the password is checked, and wrong passwords count towards that same lockout and are audited), then sign in with the provider. The provider's verified email must equal the account's email, and the domain or tenant and the person's role must be allowed. In the apps: `POST /api/v2/me/sign-in-links/providers/{provider}/nonce`, then `POST /api/v2/me/sign-in-links/providers/{provider}` with the ID token, the nonce and the password. A link nonce is bound to the account that asked for it.
- A link stores the provider's stable subject: Google `sub`; Microsoft `oid` with its `tid`. Sign-in matches the subject, never the email: an email changed at the provider cannot move a link to another account, and a new provider account with someone's address is not their link.
- A subject links to one account per school; an account has one link per provider per school.
- A link belongs to one school. A link made at school A is not found through school B's settings.
- The person removes their own links (panel `/sign-in-links`, API `DELETE /api/v2/me/sign-in-links/{link}`); another person's answers 404. The school's Super Admin removes anyone's at that school from the settings page; another school's answers 404. Audited as `sign_in.provider_linked` and `sign_in.provider_unlinked` with a one-way fingerprint of the provider account.

## Sign-in (`ProviderSignIn`)

Every step runs inside the school named by its slug (or, for linking, the person's current school), so another school's setting, link or challenge is never read.

1. The challenge is used once, by an atomic update (`consumed_at`). A replayed callback, state, nonce or ID token is refused; a replayed code is refused by the provider.
2. The ID token is checked (above) against the school's setting.
3. The link is found by the subject at this school; none means no sign-in (`not_linked`).
4. Every check of a password sign-in: an active account; no pending password change (sign in with the password and change it first); not locked by wrong passwords; a current membership here in a role the school allows (the teacher app needs Teacher, the learner app Student, the panel a staff role); a deactivated teacher refused; never platform (HQ) staff.
5. The same session or app token as a password sign-in (`PortalSession::signIn`, `AppTokens::issue`), so revocation, signing out everywhere, the per-app device limit and child mode all work as before. Child tokens are never issued by this path (parents cannot use it).

### Bound to the school

A provider sign-in proves who the person is to one school's settings only. Its app token carries the ability `sign-in-provider:{school}:{provider}:{link}` next to `*`; its panel session carries the same in `sign_in_provider`. `SetSchoolContext` (through `ProviderSessionScope`) then:

- works only in that school: another school by `X-School-Id` is 403, and the panel's school switcher has no effect;
- ends the session (403, or signed out in the panel) as soon as the link is removed, the school switches the provider off, it stops allowing the person's role, or their current membership there ends.

Password sign-ins carry no binding and are unaffected.

## Two-factor

School accounts have no second factor today; HQ staff do (`hq-two-factor.md`). HQ staff are refused here outright, so a provider sign-in never satisfies or bypasses HQ two-factor. If school accounts gain a second factor later, a provider sign-in does not satisfy it by default (owner decision below).

## Secrets

Client ids and secrets come only from the environment (`GOOGLE_SIGN_IN_CLIENT_ID`, `GOOGLE_SIGN_IN_CLIENT_SECRET`, `GOOGLE_SIGN_IN_APP_CLIENT_IDS`, `MICROSOFT_SIGN_IN_CLIENT_ID`, `MICROSOFT_SIGN_IN_CLIENT_SECRET`, `MICROSOFT_SIGN_IN_APP_CLIENT_IDS`); `.env.example` has empty placeholders. The secret is sent only to the token endpoint. Only hashes of the state and nonce are stored; the PKCE verifier is encrypted; challenges older than a day are deleted as new ones are made. No token, code, secret or email appears in the audit log or application log.

## Rate limits

`throttle:sign-in-provider`: 20 a minute per address on the start, callback, nonce and sign-in endpoints (the account is known only after the token is checked; then the per-account wrong-password lockout applies). Linking uses `throttle:password` (20 a minute per account).

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `sign_in_provider_settings` | school-owned | per school and provider: on or off, allowed domains and tenants, Workspace-only, staff roles, learners and their stages, who changed it |
| `sign_in_provider_links` | school-owned | the account, provider, tenant, stable subject, the verified email when linked, how (panel or app), last used |
| `sign_in_provider_challenges` | school-owned | purpose, channel, the account (for a link), SHA-256 of nonce and state, the encrypted PKCE verifier, the app, expiry, when used |

The migration's `down` refuses while any link exists.

## Endpoints

Panel (English source strings, af and zu translations pending a fluent check):

| Method | Path | Name | Middleware |
| --- | --- | --- | --- |
| POST | `/sign-in/{provider}` | `sign-in-provider.start` | `school.context:public`, `guest`, `throttle:sign-in-provider` |
| GET | `/sign-in/{provider}/callback` | `sign-in-provider.callback` | `school.context:public`, `throttle:sign-in-provider` |
| GET | `/sign-in-providers` | `sign-in-providers.index` | `auth`, `school.context`; Super Admin |
| PUT | `/sign-in-providers/{provider}` | `sign-in-providers.update` | same, `ProviderSettingsRequest` |
| DELETE | `/sign-in-providers/links/{link}` | `sign-in-providers.links.destroy` | same |
| GET | `/sign-in-links` | `sign-in-links.index` | `auth`, `school.context` |
| POST | `/sign-in-links/{provider}` | `sign-in-links.start` | same, `throttle:password` |
| DELETE | `/sign-in-links/{link}` | `sign-in-links.destroy` | same |

API v2 (`openapi.yaml`): `GET /auth/providers`, `POST /auth/providers/{provider}/nonce`, `POST /auth/providers/{provider}/sign-in` (public, `school.context:public`, the sign-in also `portal.sign-in`); `GET /me/sign-in-links`, `POST /me/sign-in-links/providers/{provider}/nonce`, `POST /me/sign-in-links/providers/{provider}`, `DELETE /me/sign-in-links/{link}` (`auth:sanctum`, `school.context`, `role.any`). The Dart client needs regenerating with `packages/eschool_api/tool/generate.sh`.

## Acceptance tests

`tests/Feature/Platform/ProviderSignInTest.php` (RSA keys generated per test class; the providers' JWKS and token endpoints faked):

| Test | Covers |
| --- | --- |
| `test_a_teacher_links_their_google_account_in_the_panel_then_signs_in_with_it` | panel happy path: password confirmation, PKCE/state/nonce, link by subject, sign-in, session bound, audit, only hashes stored |
| `test_a_replayed_state_or_code_is_refused` | replayed callback, replayed code, a state from another browser |
| `test_the_login_button_shows_only_where_the_school_has_the_provider_on` | provider off, other school, server not set up |
| `test_a_teacher_signs_in_to_the_teacher_app_with_a_google_id_token` | app happy path, same app token with the binding, works in the app, signing out everywhere ends it |
| `test_a_replayed_nonce_or_id_token_is_refused` | nonce replay, nonce for another app |
| `test_every_id_token_check_refuses_a_bad_token` | bad signature, wrong aud, wrong iss, expired, too old, not yet valid, nonce mismatch, email not verified, domain not allowed, consumer Google account without hd, HS256; nothing secret in the audit log |
| `test_attempts_are_limited_per_address` | the per-address limit |
| `test_a_microsoft_work_account_signs_in_only_from_an_allowed_tenant_with_its_tenant_issuer` | Microsoft happy path, wrong issuer, tenant not allowed, email not verified, guest, wrong aud, same oid in another tenant |
| `test_a_learner_signs_in_only_when_the_school_allows_learners_of_their_stage` | learners off by default, high school only by default, learner app only |
| `test_an_app_links_with_the_password_and_never_to_someone_elses_account` | wrong password, someone else's email, someone else's nonce, subject already linked, wrong owner list and unlink |
| `test_a_locked_account_cannot_link_even_with_the_right_password` | the wrong-password lockout refuses linking in the app and the panel before the password is checked; nothing linked; links again after the hour |
| `test_no_account_is_created_or_matched_by_email_and_the_subject_decides` | no matching account, no account creation, email change at the provider |
| `test_every_account_check_of_a_password_sign_in_still_applies` | disabled account, password change required, lockout, ended membership |
| `test_a_session_ends_when_its_link_membership_or_setting_stops_holding` | provider switched off, role no longer allowed, link removed, membership ended |
| `test_a_link_made_at_one_school_never_opens_another` | wrong school both ways, the token bound to its school, school B's admin cannot see or remove school A's link |
| `test_hq_staff_never_sign_in_or_link_this_way` | HQ refused in the app, the panel and linking |
| `test_the_wrong_role_is_refused` | role not allowed for sign-in and linking; parents never |
| `test_the_wrong_owner_cannot_remove_someone_elses_link_in_the_panel` | wrong owner in the panel |
| `test_only_the_school_admin_changes_the_settings_and_they_are_checked_and_audited` | wrong role, validation (consumer domain, bad domain, tenants, server not set up), audit, admin removes a link |
| `test_the_key_set_is_cached_bounded_and_fails_closed` | JWKS caching, bounded refetch, expiry, fail closed (503), oversized set |

`ApiRouteTenancyTest` lists the three public routes with reasons; `OpenApiContractTest` has a scenario for every new v2 route.

## Owner decisions (defaults until decided)

- **Linking method: the person links their own account after a password sign-in and typing the password again.** School admin pre-approval was not built: it would let a provider account open an account without the person ever proving they hold both, and would make the admin's list (or a changed email) the key.
- **Roles:** every staff role by default; learners off by default and, when on, high school only by default; parents never.
- **Two-factor:** a provider sign-in never satisfies or bypasses a second factor; HQ staff cannot use it at all. If school accounts get a second factor later, it is still asked after a provider sign-in unless the owner decides otherwise.
- **Leavers:** a leaver (read-only) membership does not allow provider sign-in; the person can still sign in with their password while their leaver access lasts.
- **One school per provider sign-in:** a provider session or token works only in the school whose settings admitted it, even for a person who belongs to several schools (they sign in again, or use their password, for another school).
- **Modern sign-in (passkeys):** not built here, and needs a WebAuthn library (none in vendor). It does not block this step; both can coexist.

## Not in this step

- Google Classroom and Microsoft Teams sync, and the public API (Action Plan 5.9, part 3).
- App screens (sign-in buttons, linked accounts) in the Flutter apps, and regenerating the Dart client.
- Admin pre-approved linking; linking learners whose account email is their GR number (the school must first record their school email on their account).
- Passkeys / modern sign-in.
- Discovery documents (endpoints are configured, not discovered), Google Workspace `hd` per-school opt-out beyond the checkbox, Microsoft personal accounts (consumer MSA), and other providers.
- Ending provider sessions in other browsers at the moment a setting changes: they end on their next request.

## Open questions

- MSAL (Microsoft's native library) does not let an app set the ID token's nonce. The Microsoft app flow may need the system browser (AppAuth, authorization code with PKCE) instead of MSAL, or a server-side code exchange for apps.
- Whether learners whose account email is a GR number should be able to link once the school records a school email address for them.
- The Entra app must be configured to include the optional `email` and `xms_edov` claims in ID tokens; without `xms_edov` (or `email_verified`) Microsoft sign-in is refused.
