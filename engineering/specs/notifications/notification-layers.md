# Three-layer notifications (Action Plan 1.4)

Every notice now has three layers and a destination:

1. a **title** (up to 128 characters, as before);
2. an **excerpt** (up to 140 characters): the text on the collapsed inbox row;
3. an optional **body** (up to 5,000 characters): the full text the app shows
   when someone opens the notice;

plus a **target**: the app path the notice opens, for example `/calendar`.

This step is the server side: the columns, `Notifier`, the producers, the v2
inbox list and the detail endpoint. The app screens come later (see "Not in
this step").

## User stories

- As a parent, I see my notifications as short rows (title and a line of
  text) and can open one to read all of it, so a long exam change or absence
  notice is not cut off at 128 characters.
- As a learner or teacher, each notice tells my app where to go, so tapping it
  opens the right screen without the app knowing every notice type.
- As a school, I know that a private notice (wellbeing, discipline, progress,
  transfers, consent, support and the other private sources) never carries its
  private content in a notification: only "something is waiting, open the
  app".
- As a parent at two schools, I only ever see the current school's notices,
  and nobody else's.

## Rules

- `Notifier::makeNotice(type, title, message, link, excerpt?, body?, target?)`.
  The first four arguments are unchanged, so every existing producer keeps
  working: the excerpt defaults to the message and the target to the link.
- The target is validated the way `Notifier::send` validates links: it must be
  an app path (starts with one `/`, no backslash, no line break, at most 512
  characters). Anything else is refused and nothing is written.
- **Private and notice-only types** keep a generic body:
  - a type is private when it starts with a prefix registered in
    `config/private_notices.php` `scopes`, and notice-only when its entry in
    `config/notification_types.php` has `'notice_only' => true` (learner
    approval requests, handle approval requests, safeguarding alerts and
    relief-cover assignments);
  - for these types `makeNotice` ignores any excerpt or body it is given: the
    excerpt is the generic message and no body is stored;
  - the inbox and the detail endpoint also show only the generic message for
    these types, even for a row written some other way.
- Producers supply the title, excerpt and target. Most producers' messages are
  already a one-line summary, so the defaults are right for them. Producers
  with more to say also pass a body:
  - exam changes (`academics.exam_changed`): the new date, time and venue, what
    it was before, and where to find the full exam timetable; exam removals
    say where to look;
  - absence alerts and register amendments (`attendance.absence`,
    `academics.register_amended`): who to contact if something is wrong.
  School messages (`communication.*`) stay generic: the message itself is read
  in Messages, under its own access rules.
- Push and email are unchanged: they still send the title and message.
- Installed apps are unchanged: the v1 `get-notification` lists do not show
  the excerpt, body or target (the model hides them), so their shape is the
  same.

## The inbox API (v2)

`GET /api/v2/me/notifications?page=N` (25 a page, page 1 to 1000): the
signed-in user's notices in the current school, newest first. These are the
notices sent to them (`user_notifications`) and older whole-school notices,
with every private source filtered in SQL first (`NotificationVisibility`,
`PrivateNoticeScopes`), exactly as the v1 lists do. Each item:
`id, type, title, excerpt, target, has_body, date`. The body is never read
for the list. A missing target falls back to the delivery link, then to the
type's default screen.

`GET /api/v2/me/notifications/{id}`: one notice with its `body`. The service
(`NotificationInbox::show`) first finds the notice through the same scoped
query (so another user's or another school's notice, or a private source that
no longer allows the user, is 404), then runs the same fresh access check the
notice's delivery uses (`DeliveryGuard::permits`: active user and membership,
an active school, the source records such as the report card, register entry,
content version, handle proposal or school message, every private-source rule
and the transfer, progress and cover guards), and for registered types that
the type is still offered to the user (module on, portal, permission). Only
then is the text loaded. Without a stored body, `body` repeats the excerpt.

Both answer `Cache-Control: private, no-store`. Both need a signed-in user with
a role (`role.any`) and a school context; asking in a school where the user
has no membership is 403. Impersonated and across-schools contexts are 403.

## Data

`notifications` gains three nullable columns (migration
`2026_11_01_070000_add_notification_layers`): `excerpt` varchar(140), `body`
text and `target` varchar(512). The table was already school-owned
(`school_id`, `BelongsToSchool`); no new table. Rolling back refuses while any
row holds an excerpt, body or target.

## Acceptance tests

`tests/Feature/Platform/NotificationLayersTest.php`:

| Test | What it proves |
| --- | --- |
| `test_make_notice_stores_three_layers_and_a_target` | A producer's title, excerpt, body and target are stored; the excerpt is cut at 140 and the body at 5,000 characters. |
| `test_legacy_producers_still_work_with_defaults` | Four-argument `makeNotice` and the legacy `sendSimpleNotification` helper still work: excerpt is the message, target the link, no body; v1 list rows show none of the new fields. |
| `test_targets_must_be_app_paths` | Foreign URLs, `//host`, relative paths, line breaks and backslashes are refused; nothing is written. |
| `test_happy_path_list_then_detail` | The list returns title, excerpt, target and has_body (never the body); the detail returns the full body, or the excerpt when there is none. |
| `test_wrong_owner_another_users_notice_is_not_found` | Another user's notice is not listed and its detail is 404; nothing changes. |
| `test_wrong_school_another_schools_notice_is_not_found` | A School B notice, even with a forged link to a School A user, is not listed and is 404 from School A; School B's context is 403; the School B row is unchanged and nothing of School B is in any answer. |
| `test_wrong_role_an_account_without_a_role_is_refused` | An account with no role gets 403 on the list and the detail. |
| `test_a_private_type_never_exposes_content_in_excerpt_or_body` | Private-scope and notice-only types store only the generic message and no body whatever the caller passes; a row forced to hold private text still shows only the generic message; a withdrawn private source is not listed and its detail is 404. |
| `test_detail_rechecks_the_source_freshly_before_the_body` | A listed notice whose source no longer allows the reader (a report card that no longer exists) is 404 at the detail, through HTTP and the service. |
| `test_page_validation` | Page 0 is refused with 422; later pages answer. |

`tests/Feature/Contracts/OpenApiContractTest.php` checks both operations
against `openapi.yaml` for a parent, learner, teacher and school-created staff,
without a token, without a role, page validation and another user's notice.

## Not in this step

- The apps: one notifications tab per app (top right), collapsed rows, the full
  view, and back to the tab then home (`packages/eschool_notifications`).
- Generic routing by `target` replacing the per-type routing in
  `generalNotificationUtility.dart`.
- Regenerating the Dart SDK in `packages/eschool_api` for the two new
  operations (there is no Dart toolchain here).
- Read and unread state in the v2 inbox, and marking notices read.
- Sending the excerpt in push and the body in email.
- A body field on the panel's "Send notification" form (it still writes title
  and message only, so its notices show the message as the excerpt).
