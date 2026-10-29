# Learner diary (notes, to-dos and school items)

The diary replaces a learner's paper diary. It has two layers:

- **Private entries.** Notes and to-dos the learner writes. Only the learner can read them.
- **School items.** Work the school has already published for that learner (homework, school events, holidays), placed on the right day automatically. The learner can tick a school item off and add a private note to it.

The module is `diary`. It is off by default and each school switches it on. Requested by the product owner on 29 October 2026, who also decided that a learner's diary is private to that learner: parents, teachers, school staff and platform staff cannot see what the learner writes.

## Privacy

- Only the signed-in learner reads or changes their own entries and item states. There is no panel page, parent route, teacher route, export, search index, report, AI source or analytics for them. Every route refuses Parent, Teacher and staff roles. They also refuse platform staff identities, impersonation and across-school context (the `TutorAccess` rules).
- Titles, bodies and item notes are encrypted at rest with a per-learner key: `DiaryCipher` derives it with HKDF-SHA256 from `DIARY_KEY` (falling back to `APP_KEY`), using the school id, user id and a fixed purpose string. A database copy reveals nothing; a database copy plus `APP_KEY` alone does not either when `DIARY_KEY` is set. Each ciphertext starts with a short reference to the root key that made it, so setting `DIARY_KEY` on a school that started on `APP_KEY` keeps older text readable while new text uses `DIARY_KEY`. Removing or changing a root key that still has text makes that text unreadable, so `DIARY_KEY` is a deliberate, separate secret that is never rotated casually.
- Dates, `kind`, done and reminder times stay plain, so the server can order and bound queries; they are visible only to the owner through the API.
- Deleting an entry is permanent (no soft delete). The rows hold the owner's `user_id` and are removed when the learner's school data is erased.
- Responses are private and uncacheable (`PrivateDomainResponse`). Logs never include entry text.
- Safeguarding: nothing is monitored. The app shows a "Talk to someone" link to the existing wellbeing reporting, which the learner chooses to use. Whether a parent may request a child's notes under POPIA (especially for young learners) is for the school's Information Officer to confirm before launch; this feature gives nobody a way to read them.

## School items (automatic allocation)

School items are a projection worked out when the diary is read, not copies. A changed due date, a deleted assignment or a learner moving class therefore shows correctly at once, and nothing needs re-syncing. Each source decides which learner it applies to with the same rules its own screens use.

| Source | Kind | Who gets it | When it shows |
|---|---|---|---|
| Assignments | `homework` | Learners in the assignment's class section this session year; for an elective subject only learners with a `student_subjects` row for it | On its due date, and every day after while it is not submitted |
| Holidays | `holiday` | Everyone in the school | Each day of the holiday in the range |
| Events (school calendar) | `event` | Everyone in the school | From start to end date |

A homework item carries its subject, its due time, whether the learner has submitted it, and its status and mark once marked (the learner already sees these in the app). Submitted homework counts as done; the learner can also tick any item off themselves.

The learner layer for a school item (`diary_item_states`) holds only `done_at`, `remind_at` and an encrypted note, keyed by owner and `(source, source_id)`. If its source disappears, the state is ignored and removed by the next write to that key. Deleted sources are never revealed.

Later sources (exam timetable and revision reminders, activity group sessions, consent forms, teacher notes) follow the same contract: a source returns items for one learner and a date range, and is filtered by the learner's current memberships.

## API (v2)

`/api/v2/diary`, guarded by `auth:sanctum`, school context, `learner.approved` and `module:diary`. The learner must be a current, guardian-approved Student of the school.

- `GET diary?from=YYYY-MM-DD&to=YYYY-MM-DD`: up to 62 days. Returns the learner's entries whose date (or due date) falls in the range, undated to-dos that are still open, the school items in the range, and the server's school-local today.
- `POST diary/entries`: creates a `note` or `todo` (`operation_id` makes a retry return the original entry).
- `PUT diary/entries/{entry}`: replaces it (a field left out is cleared); `revision` must match (409 otherwise).
- `DELETE diary/entries/{entry}`: deletes it permanently.
- `GET diary/reminders`: every reminder still to come (open entries, and school items that still apply and are not handed in), with its UTC instant and text, up to 500.
- `PUT diary/items/{source}/{id}/state`: sets done, a reminder and a private note on a school item that currently applies to the learner (404 otherwise).

Another learner's entry, or an item that does not apply to the learner, is 404 (no existence leak). Validation uses the v2 envelope.

## Learner app

The learner app opens the diary from the home screen (**My diary**) and from `/learning/diary`. Only an approved learner of the current school sees either; a parent's app has no route to it.

- A page per day with a week strip. Each page shows the school items for that day, the learner's to-dos and notes for that day, and on today's page the open to-dos without a date ("Anytime").
- To-dos and school items are ticked off from the list. Submitted homework is ticked by the school and cannot be unticked. Tapping an item opens its private note and reminder; tapping an entry opens it to edit or delete.
- A note always has a date; a to-do may have none.
- The diary and everything it opens (editor, item sheet, dialogs) run in a navigator of their own inside the session boundary. Any change of account, school, role, approval or module removes all of it at once; a routine context refresh that leaves the same learner keeps an open editor. From the moment the app leaves the front (`inactive`), the diary is covered and its loaded text discarded, so the app switcher and a borrowed phone show nothing.
- Reminders are local notifications scheduled on the learner's phone, on their own Android channel (`diary_reminders`) with private lock-screen visibility. Whenever the diary loads or changes, the phone replaces all its diary reminders with the server's complete list of upcoming ones (`GET diary/reminders`, instants in UTC), so moved, done, deleted and unviewed entries are always right. Signing out cancels every diary reminder on the phone and stops a sync in progress. The server never sends a diary push.
- A **Talk to someone** link opens the existing wellbeing reporting when that module is on. Nothing in the diary is passed to it.

## Limits

Up to 2,000 entries per learner. A title is up to 200 characters, a body up to 10,000 and an item note up to 2,000.
