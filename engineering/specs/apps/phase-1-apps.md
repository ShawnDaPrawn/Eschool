# Phase 1 in the apps: switchers, deep links and the shared screens

Phase 1, work stream T5 (Apps). Owner role: App. The Build Bible, section 7 (its app work and the UX checks for the child switcher, the Notifications screen and My school) and section 3.4 (shared packages); [ADR 0003](../../adr/0003-app-packages-and-web.md). Builds on [foundations.md](foundations.md) and [web-builds.md](web-builds.md).

The apps are built ahead of the server, against the contract in [`engineering/phases/phase-1.md`](../../phases/phase-1.md) ("Contract between streams"). T1 adds schools and `X-School-Id`, T2 approvals, invitations and avatars, T3 notification preferences, My school and the rest of `/me/context`. Until an endpoint is deployed the app behaves as before: a screen whose endpoint answers 404 keeps its entry point hidden, and `/me/context` keys that are missing mean "not available yet".

## What changes

| Part | Before | After |
| --- | --- | --- |
| Which child | A parent picked a child on home and each child screen was handed the child's id on every tap | `ChildSelectionCubit` (`eschool_session`) holds the child on show, in Hive, restored on start and cleared at sign-out. Every child screen reads it; changing child rebuilds every open child screen for the new child. A persistent switcher shows on the child screens: a bar in the dark header on phones and tablets, a list of the children beside the screen on desktops |
| Which school | One school | `SchoolSelectionCubit` lists `/me/context` `schools`; a person at several schools gets a crest button in the home header that switches school. The choice is kept in Hive and sent as `X-School-Id` on every call (`SchoolHeaderInterceptor`). A guardian's child screens are sent for the child's school |
| `/me/context` | Fetched at start-up and sign-in | Decoded part by part with the generated `eschool_api` models; cached with the time it was fetched; fetched again after a school switch, on resume when older than five minutes, and on a `context.changed` push |
| New screens | none | Built once in the packages: Notifications settings (`eschool_notifications`), Approvals, Invitation, My school and Profile picture (new package `eschool_people`) |
| Avatars | none | Sixteen SVG avatars in `eschool_core/assets/avatars/`, `AvatarSet`, `PersonAvatar` |
| Links | Portal addresses and `navigateed://` mapped to screens; no native app links | `/notifications/settings`, `/my-school`, `/approvals`, `/invite/<token>`, `/profile-picture`; a push's `link` opens after switching to its `school_id`; Android App Links and iOS universal links for the portal hosts |

Routing stays `Routes` and `onGenerateRoute` with the `DeepLinkParser` table, as the Build Bible's section 3.4 keeps it: the new paths are entries of that table, which the web portals read from the address bar too. No router package was added.

## User stories

- **Parent with two children:** I pick a child once (on home, or in the switcher on any child screen) and every screen shows that child until I pick another; the app remembers my choice; the switcher shows each child's picture, name and school.
- **Parent with children at two schools:** picking a child at the other school moves the app to that school; the school switcher shows a child at the school I switch to; My school lets me pick which school.
- **Teacher or learner at two schools:** a crest in the home header switches school and takes me back home; at one school I see no switcher.
- **Parent:** in Settings, Approvals lists my children waiting for my approval and the ones I approved; I read the consent text before approving; withdrawing asks me first.
- **New parent:** the invitation link opens the app (or the portal) even before I have an account; I see the school and my children, set a password, and I am signed in.
- **Everyone:** Notifications settings shows each kind of notice with a switch per channel; the school's mandatory notices show a lock and why; a change is saved at once.
- **Everyone:** My school shows the crest, name, contacts (a tap calls or writes), documents and term dates.
- **Everyone:** I choose an avatar or upload a photo; my photo shows "waiting for the school" until approved.
- **Everyone:** tapping a push opens its screen, at the right school.

## Acceptance tests

| Given | When | Then | Test |
| --- | --- | --- | --- |
| A parent with two children, a Phase 0 server (no `children` in context) | they sign in | the switcher lists both, from the v1 sign-in answer, the first picked | `child_switcher_test` (app), `child_selection_test` |
| The server sends `/me/context` `children` | the context loads | the switcher lists those children, with their schools; a child the v1 answer does not list still opens | `child_switcher_test` (app) |
| A parent on a child screen | they pick the other child in the switcher | the open screen is built afresh for that child (new fetch); the choice is kept | `child_selection_test`, `child_switch_smoke_test` |
| A child picked last time | the app starts | that child is picked again; a child no longer listed falls back to the first | `child_selection_test` |
| Sign-out | — | the children, the picked child and the school are forgotten | `child_selection_test`, `school_selection_test` |
| One child | a child screen opens | no switcher | `child_selection_test` |
| Widths 390, 820, 1280 | a child screen opens | a bar on top (phone, tablet), a list beside the screen (desktop) | `child_selection_test`, `child_switcher_test` (app) |
| A user at one school | home opens | no school switcher; no `X-School-Id` | `school_selection_test` |
| A user at two schools | they pick the other school | it is kept, `X-School-Id` carries it, `/me/context` is fetched again, home opens | `school_selection_test` |
| A school the user left | the context loads | it is no longer sent | `school_selection_test` |
| A guardian picks a child at the other school | — | `X-School-Id` follows the child's `school.id` and the context is fetched for it | `school_selection_test` (`follow`), `sessionWatcher.dart` |
| A cached context of 4 / 6 minutes | the app resumes | not fetched / fetched | `me_context_cubit_test` |
| A `context.changed` push in front / in the background | — | fetched at once / noted and fetched at the next resume; never shown or counted | `notifications_test`, `me_context_cubit_test` |
| A context with unknown keys, missing keys or malformed parts | it is read | unknown keys are ignored, missing parts are `null`, the rest decodes | `me_context_test` |
| Notification preferences with a locked channel | the screen opens | a lock and the reason; the switch cannot be turned | `notification_settings_test`, `notification_settings_smoke_test` |
| An optional email switch | a parent turns it off | one tap; `PUT {type, channel, enabled}`; a 422 puts it back with the server's reason | `notification_settings_test`, `notification_settings_smoke_test` |
| Pending and approved children | Approvals opens | two lists; approve shows the consent and sends its version; withdraw asks first | `approvals_test` |
| An invitation needing a password | the parent accepts | password rules checked in the app; the accept answer signs the parent in | `invitation_test` |
| An expired or used invitation | the link opens | "ask the school for a new one" | `invitation_test` |
| A school with contacts, documents and term dates; a parent with children at two schools | My school opens | each shows; a tap calls, writes or opens; the parent picks the school | `my_school_test` |
| The avatar screen | a person taps an avatar / uploads a photo | saved at once / shows "waiting for the school" | `avatar_test` |
| Any new endpoint answering 404 | Settings opens | its row is hidden | `endpoint_availability_test`, `notification_settings_smoke_test` |
| The new paths | parsed as portal addresses or push links | each opens its route; `/invite/<token>` opens before sign-in | `routes_test` (both apps), `deep_link_parser_test` |
| A push with `link` and `school_id` | it is tapped | the school switches first, then the screen opens | `deep_link_parser_test` (`openPushLink`) |
| Every new control | read by a screen reader | it has a name; tap targets are at least 48 px | the accessibility tests in each package's test files |
| Every new label | the apps' tests run | it is in every language file of both apps | `shared_label_keys_test`, `translations_test` |

Refusals (wrong role, another school's record, another user's record) are the server's to enforce and T1 to T3 test them. On the device: a guardian can only pick one of their own children (`ChildSelectionCubit.select`), a user can only pick one of their own schools (`SchoolSelectionCubit.select`), and the approvals entry is shown to parents only.

## Data changes

None on the server. On the device, new keys in Hive's `authBox` (no key is renamed): `selectedChildId`, `selectedSchoolId` and `meContextFetchedAt`; `meContext` now keeps the server's answer as it came. A new SharedPreferences key, `contextChanged`, notes a `context.changed` push that arrived in the background.

## Endpoints

Read and written through each app's `ApiClient` (token or cookie, `X-School-Id`, 401 handling). The request and response models of the new endpoints are written by hand next to their repositories, reusing the generated `SchoolSummary`, and are to be replaced by the generated `eschool_api` client once T2 and T3 add the endpoints to `openapi.yaml`. Every answer is read inside the v2 envelope's `data`.

| Screen | Call | The app sends | The app reads |
| --- | --- | --- | --- |
| Session | `GET /api/v2/me/context` | — | `user` (with `roles`), `schools[] {school: SchoolSummary, roles}`, `current_school` (generated `CurrentSchool`), `modules[] {key, read_only}`, `notification_rules`, `feature_flags`, `sign_in_methods`, `children[]` (generated `Child`: `id`, `user_id`, `first_name`, `last_name`, `name`, `image`, `school`, plus `student_number`, `approved`, `homeroom`), `approval {status, approved_by_count}` |
| Every call | any | `X-School-Id: <id>` once a school is chosen (a guardian's: the child's `school.id`) | — |
| Notifications settings | `GET /api/v2/me/notification-preferences` | — | list of `{type, label, module, channels: {push, email, in_app: {enabled, locked, reason}}}`; a channel left out is not offered |
| | `PUT /api/v2/me/notification-preferences` | JSON `{type, channel, enabled}` | 2xx, or 422 with `message` (shown) |
| Approvals | `GET /api/v2/guardian/approvals` | — | list of `{learner: {id, name, image, student_number, school}, status: pending/approved/withdrawn, requested_at, consent: {version, text}}` |
| | `POST /api/v2/guardian/approvals/{learner}` | JSON `{decision: approve/withdraw, consent_version}` | 2xx (the list is fetched again), or a 4xx `message` (shown) |
| Invitation | `GET /api/v2/guardian/invitations/{token}` (no token sent) | — | `{school: SchoolSummary, email, learners: [{id, name}], expires_at, needs_password}`; 404 means unknown, expired or used |
| | `POST /api/v2/guardian/invitations/{token}/accept` (no token sent) | JSON `{password?}` plus the device's `fcm_id` and `device_type` as at any sign-in | the parent sign-in's answer: `data` (the parent, as `parent/login` sends it) and `token` |
| My school | `GET /api/v2/schools/{school}/my-school` | — | `{school: SchoolSummary, website, phone, email, address, documents: [{title, kind, url}], term_dates: [{name, starts_on, ends_on}]}` |
| Profile picture | `GET /api/v2/me/avatar` | — | `{kind: set/photo, key, url, status: approved/pending}` (also its availability probe) |
| | `PUT /api/v2/me/avatar` | JSON `{avatar_key}` | the same shape |
| | `POST /api/v2/me/avatar` | multipart `photo` and `_method=PUT` (PHP reads no multipart body on a PUT) | the same shape |

Push data the apps read: `type` (`context.changed` is silent), `link` (an app path) and `school_id`.

## Screens

- **Child switcher** (`ChildSwitcherFrame`, `ChildSwitcherBar`, `ChildSwitcherRail`, `showChildPicker`): around every parent child screen (child details, assignments, teachers, attendance, timetable, calendar, exams, results, reports, fees, leaves). Home's child cards pick the child and show each child's school.
- **School switcher** (`SchoolSwitcherButton`, `showSchoolPicker`): in the learner, parent and teacher home headers.
- **Notifications settings**: Settings, and a gear in the Notifications screen's header.
- **Approvals** (parents), **My school**, **Profile picture**: Settings.
- **Invitation**: from its link only.

Each has loading, empty, not-yet-available and error states, uses `ScreenScaffold` (the apps' dark header) and the theme's roles only, and works at 390, 820 and 1280 px.

## Notification types

None added by the apps. They handle `context.changed` (data-only, silent) and any push with `link` and `school_id`. T3 registers the types; the apps show whatever `GET /me/notification-preferences` lists.

## Module switch

None of these screens is a switchable module (school set-up, people and approval, and notification settings are always on). `MeContext.isModuleReadOnly()` is there for read-only modules: their screens show without the actions that change things.

## Security notes

- The child and school pickers only offer the user's own children and schools; the server checks `X-School-Id` against the user's memberships (T1).
- The invitation token is only in the link and in the two calls that need it; those calls carry no session token. Android App Links (`autoVerify`) and iOS universal links bind the portal hosts to the apps, so another app cannot claim an invitation link; no custom scheme is declared natively.
- The avatar photo is picked by the user and uploaded as multipart; the server scans and approves it (T2). No new secret, key or token is stored on the device.
- `assetlinks.json` and `apple-app-site-association` hold placeholders (`RELEASE_SIGNING_SHA256_FINGERPRINT`, `APPLE_TEAM_ID`) that the release runbook fills; neither is a secret.

## Self-test steps

1. As the parent with two children at School A: open a child, switch child in the bar, check every open screen shows the new child; restart the app: the same child. At 1280 px in the portal: the list beside the screen.
2. As the parent with a child at each school: pick the child at School B; check a request carries `X-School-Id` of School B and the header crest shows the switcher; switch school back: home opens and shows a child at School A.
3. As a teacher at one school: no school switcher. As a user at two: switch, home opens, `/me/context` is fetched again.
4. In Settings, open Notifications settings: turn off email for notices (one tap); a mandatory notice shows the lock and its reason.
5. As a parent with a child waiting: Approvals, read the consent, approve; withdraw with the confirmation.
6. Open `https://staging-app.navigateed.co.za/invite/<token>` while signed out: the invitation opens, set a password, signed in.
7. Open My school: tap the phone number (the dialler opens) and the fee schedule.
8. Choose an avatar; upload a photo: "waiting for the school".
9. Send a test push with `link: /my-school` and the other school's `school_id`: tapping it switches school and opens My school.
10. On Android, `adb shell am start -a android.intent.action.VIEW -d https://staging-app.navigateed.co.za/my-school`: the app opens My school (after the fingerprint is filled in).
