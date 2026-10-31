# Rich push and silent timetable refresh

Action Plan 2.9 (rich push) and 1.9 (next-lesson reminders), the server part. Owner role: Backend (notifications). Builds on [quiet hours](quiet-hours.md), [push devices](push-devices.md) and [notification rules](notification-rules.md). The payloads are built in `Notifier::push` and `PushPresentation`; the silent push in `TimetableRefreshPush`.

## User stories

- A parent asked for consent sees "Yes" and "No" on the notification. Either one opens the app on the request, where the parent reads it and confirms. Nothing is consented from the lock screen.
- A teacher gets a chat message showing the sender's name and photo, with a "Reply" button that opens the conversation.
- A staff member sees "Acknowledge" on an urgent notice or a safeguarding alert; it opens the notice in the app.
- A parent whose phone lies on a table never shows a wellbeing, discipline, consent, progress or other private notice's content on the lock screen: only "School notification / Open NavigateEd to view this notice."
- When the timetable changes (a lesson is saved, or a short day is planned or cancelled), the phones of the affected learners, their guardians and the teachers quietly fetch the timetable again, so the morning's next-lesson reminders stay right.

## Rules

### Buttons (`push_actions`)

- A type's buttons are listed in `config/notification_types.php` as `push_actions`, ids only:
  - `consent.requested`: `consent_yes`, `consent_no` (iOS category `consent`)
  - `comms.message` (chat) and `communication.message`: `reply` (category `reply`)
  - `comms.urgent` and `safeguarding.alert`: `acknowledge` (category `acknowledge`)
  - `rsvp_yes`, `rsvp_no` (category `rsvp`) are known ids, but no events/RSVP notification type exists yet, so no type uses them.
- The push's data carries `actions` (the ids, comma-separated) and `category`. iOS also gets `apns.payload.aps.category`.
- **Every button only opens the app to confirm.** A button is an id, never a URL, and no button performs a write from the notification. The tap opens the push's usual `link`; the app shows the request and the person confirms there, signed in, with the normal checks. So a lock-screen tap can never consent, reply or RSVP on someone's behalf.
- Ids outside `PushPresentation::ACTIONS`, or a list that is not one of the known categories, are dropped.

### Sender

- `comms.message` and `communication.message` pushes carry `sender_name` and `sender_avatar` (or empty).
- They come from the producer's `sender_info`. The chat producers already put the sender's name and profile photo URL there; the photo is the same public profile URL the chat API and the old payload already carry, so nothing new is published.
- `sender_avatar` is sent only when it is an absolute http(s) URL to a file (no credentials, no control characters, not the bare storage folder of a person with no photo); otherwise it is empty.
- School messages (`communication.message`) carry no sender today, so both fields are empty.
- iOS pushes with an avatar get `mutable-content: 1`, so the app's notification service extension can fetch the picture.

### Lock-screen privacy

- Private types are those under a `private_notices.scopes` prefix (consent, wellbeing, discipline, progress, digest, group chat, school assistance, consultations, transfers, recognition, the fee statement reminder), plus any type flagged `notice_only` (no type has the flag on this base).
- For them the push's visible title and body are always the generic "School notification" and "Open NavigateEd to view this notice.", whatever the producer passed. That covers the data `title` and `body` (which the Android apps display), the iOS `notification` block, and the image (always empty). The data also carries `visibility=private`.
- With the `android_private_visibility` switch on, Android pushes for private types also get `android.notification` with the generic text and `visibility: PRIVATE`.
- The in-app inbox is unchanged: the person sees the notice after opening the app, under the usual checks.

### Silent timetable refresh (1.9)

- The outbox event `academics.timetable_changed` (recorded by timetable saves and by short days, both inside their transaction) now has a listener. After the change commits, it sends each device of the affected people a silent push.
- Affected people: the learners on the changed lessons' rosters today (in the school's time zone), their current guardians, and the lessons' teachers. Only active members of that school with status active are included.
- The push is data only, `{type: "timetable.refresh", school_id}`, with no notification block. It never carries a lesson, learner, subject, room or time.
  - iOS: `apns-push-type: background`, `apns-priority: 5`, `apns.payload.aps.content-available = 1`.
  - Android: a data-only message with `android.priority = normal`.
- It goes through `PushQueue::toDevice`, so each device has its own job and `notification_deliveries` row (`push.timetable.refresh`) and its own retries.
- When the job runs it checks again that the person is still an active member of the school and the device is still theirs; otherwise the row is marked suppressed and nothing is sent.
- **Quiet hours don't hold a silent push**: it shows nothing and makes no sound. Notification choices don't apply either; it is not a notice.
- Exam changes send their own `academics.exam_changed` notice and do not record `academics.timetable_changed`, so they send no refresh in this step.
- A school's event finds only its own lessons: another school's lesson id in the event finds nothing, so no other school's device is ever reached.

### Switches (`config/platform.php`, `rich_push`)

Both are **off by default** until the apps that handle them are released:

- `timetable_refresh` (`PUSH_TIMETABLE_REFRESH`). Today's Android apps would show an empty local notice for a push type they don't know.
- `android_private_visibility` (`PUSH_ANDROID_PRIVATE_VISIBILITY`). Today's Android apps build their own notice from the data, so with an `android.notification` block the system may show a second (generic) copy.

The buttons, sender fields and generic private text are always on: the apps ignore data keys they don't know.

## Data

No table, no migration, no API or OpenAPI change. New data keys in the push payload only: `actions`, `category`, `sender_name`, `sender_avatar`, `visibility`, and the iOS `aps.category` and `mutable-content`. Two new translation keys for the generic text.

## Acceptance tests (`Platform/RichPushTest`)

| Test | Given | Then |
| --- | --- | --- |
| `test_consent_and_urgent_notices_carry_their_buttons_on_both_platforms` | a parent with an Android phone and an iPhone; a consent request, an urgent notice, a plain notice | consent: `consent_yes,consent_no`, category `consent` on both, iOS `aps.category`; urgent: `acknowledge`, still time-sensitive; a plain notice has no buttons |
| `test_actions_are_known_ids_that_only_open_the_app_never_urls` | every type with `push_actions` | each id is a known id (`[a-z_]+`), each list has a category; the pushed ids have no `/` or `:`; the tap opens the app path |
| `test_a_direct_message_names_its_sender_with_the_profile_photo_it_already_carries` | a chat message with the sender's photo | `sender_name`, `sender_avatar`, `reply`, iOS category and `mutable-content`; a bare folder, script, credentialed or empty URL is sent as empty; a school message has empty sender fields |
| `test_private_notices_never_show_their_content_on_either_platform` | six private types pushed with a learner's name, details and an image | Android data and `android.notification` show only the generic text with `PRIVATE`; iOS `notification` the same; no image, no sender; ordinary notices keep their text; with the switch off, no Android block but still the generic text |
| `test_a_timetable_change_silently_refreshes_the_learner_guardian_and_teacher_devices` | a lesson saved for Asha's group; Asha, her father, the teacher and Ben (not in the group) each with two devices | six silent pushes, none to Ben; data only type and school id; iOS background/5/content-available; Android normal; no lesson or learner text; six delivery rows for School A |
| `test_a_short_day_also_refreshes_and_the_switch_turns_it_off` | a short day planned | nothing queued before the outbox runs, then refresh pushes to Asha's devices; switch off: nothing |
| `test_a_school_a_change_never_reaches_school_b_devices` | School B learners and parents with devices; a School A change; a School B lesson id in a School A event; a School A lesson id in School B | no School B token, no School B delivery rows |
| `test_a_silent_push_is_not_held_by_quiet_hours_but_stops_for_a_departed_person_or_moved_device` | a parent in quiet hours at 23:00; a device moved to another account; a teacher whose membership ended | sent at once, not re-queued; the other two suppressed, nothing sent |

## Not in this step

- **The apps (`packages/eschool_notifications`)**, before the switches can be turned on:
  - Register the iOS categories `consent`, `reply`, `acknowledge`, `rsvp` and Android action buttons from `actions`. Every button opens the app on the push's link and asks the person to confirm. Inline reply (text input) is drafted in the app and sent from the app, after it opens.
  - Show `sender_name` and `sender_avatar` (Android large icon; an iOS notification service extension for the picture).
  - Use `visibility=private` for the local notice's lock-screen privacy, and skip the local copy when the system already showed `android.notification`. Then turn on `PUSH_ANDROID_PRIVATE_VISIBILITY`.
  - Handle `timetable.refresh` silently, like `context.changed`: fetch the timetable, reschedule the day's next-lesson and break reminders locally (Awesome Notifications scheduling), respect quiet hours for those local reminders, and never count or show the push. Then turn on `PUSH_TIMETABLE_REFRESH`.
- An events/RSVP notification type: none exists, so `rsvp_yes`/`rsvp_no` are reserved but unused.
- A sender for school messages (`communication.message`): the producer deliberately carries ids only.
- Avatars behind access control: profile photos are still public storage URLs (see profile-legacy-files.md); moving them behind auth is separate.
- A refresh on exam changes, which don't change lessons in this step.
