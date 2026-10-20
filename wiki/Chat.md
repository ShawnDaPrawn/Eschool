One-to-one chat lets teachers talk to their students and to those students' parents from the two Flutter apps. This page covers the tables, the API endpoints in the three app controllers, who may message whom, attachments and limits, read state, the push sent for each message, the admin chat settings, and how old messages are removed.

User-facing descriptions are in the Docusaurus pages [Chat (features)](https://shawndaprawn.github.io/Eschool/features/chat/), [Chat Settings](https://shawndaprawn.github.io/Eschool/admin-panel/chat-settings/) and [Chat Message Constraints](https://shawndaprawn.github.io/Eschool/mobile-app/chat-constraints/).

## Data model

Created by [`2023_12_08_122524_all_chat_table.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/database/migrations/2023_12_08_122524_all_chat_table.php).

| Model (table) | Columns | Use |
|---|---|---|
| `ChatMessage` (`chat_messages`) | `modal_id`, `modal_type` (`morphs('modal')`), `sender_id` (FK `users`, cascade), `body` (text), `date` (datetime), timestamps | One message. `modal_id` is the **receiver's** `users.id`; `modal_type` is always the string `App/Models/User` (forward slashes, so the `modal()` morph relation does not resolve; the code queries `modal_id` directly). `file()` is `hasMany(ChatFile, 'message_id')`. |
| `ChatFile` (`chat_files`) | `message_id` (FK `chat_messages`, cascade), `file_name` (path on the public disk), `file_type` (always 1), timestamps | One attachment |
| `ReadMessage` (`read_messages`) | `modal_id`, `modal_type`, `user_id` (FK `users`), `last_read_message_id` (FK `chat_messages`, cascade), timestamps | Read pointer: the row with `modal_id = reader` and `user_id = other person` stores the id of the last message from that person the reader has read |
| `ChatRoom` (`chat_rooms`), `ChatMember` (`chat_members`) | `name`; `chat_room_id`, `user_id` | Defined (`ChatRoom::members()`, `messages()`), but no controller uses them: there are no group chats. |

A conversation between users A and B is every `chat_messages` row with (`sender_id` A, `modal_id` B) or (`sender_id` B, `modal_id` A).

## API endpoints

Each app role has the same four endpoints under its prefix, all behind `auth:sanctum` and the role middleware (`role:Student` + `student.session.active`, `role:Parent` + `parent.children.session.active`, `role:Teacher`). The parent chat routes are outside the `CheckChild` group, so no `child_id` is needed. See [API-Overview](API-Overview) for the shared response format.

| Endpoint | Student (`StudentApiController`) | Parent (`ParentApiController`) | Teacher (`TeacherApiController`) |
|---|---|---|---|
| `GET get-user-list` | Teachers the student may chat with. Params `offset`, `limit` (no limit means all), `search` (first/last name). | Teachers of the parent's children. Params `offset`, `limit`, `search`. Items also carry `class_teacher` and `student_name` (which children they teach). | `isParent` = 0 lists students, 1 lists parents, of the teacher's class sections. Params `offset`, `limit`, `search` (filtered and paged after the list is built). |
| `POST send-message` | `receiver_id`, `message`, `file[]` | same | same |
| `POST get-user-message` | `user_id`, `offset`, `limit` (1 to 100, default 100) | `user_id`, `offset`, `limit` | `user_id`, `offset`, `limit` |
| `POST read-all-message` | `user_id` | `user_id` | `user_id` |

`get-user-list` returns `{items, total_items, total_unread_users}`; each item has the contact's `id`, `user_id`, names, `image`, contact details, `subjects`, `last_message` (`id`, `body`, `date`) and `unread_message`, sorted by the last message date, newest first. Teacher items for students add `roll_no`, `admission_no`, `gender`, `dob`, `address`, `class_name`; for parents, `occupation` and their `children`.

`send-message` returns the new message as `{id, sender_id, body, date, files[]}`, where `files` are full URLs (`asset('storage/<file_name>')`).

`get-user-message` returns `{items, total_items}`, newest first, each item `{id, sender_id, body, date, files[]}`. The parent version selects exactly the two directions of the conversation; the student and teacher versions select rows whose sender and receiver are each one of the two users.

`read-all-message` sets `last_read_message_id` on the caller's read pointer for `user_id` to the newest message that user sent the caller. It updates an existing pointer only; if the other person has never messaged the caller, nothing changes.

## Who may chat with whom

The receiver check runs on every `send-message`; the lists use the same rules (commit `839c9eb` added the student checks, `c890813` the parent checks, `77fe8ed` the teacher checks).

| Sender | Allowed receivers | Code |
|---|---|---|
| Student | Class teachers of the student's class section and subject teachers of the student's subjects (core subjects plus chosen electives) in that class section, for the current session year. If the class uses semesters this session year (`ClassSessionConfig::classHasSemester`) and a semester is current, only that semester's assignments count. | `StudentApiController::chatTeacherIds()` |
| Parent | The same set, for every child with a session in the current session year | `ParentApiController::chatTeacherScope()`, `chatAllowedUserIds()` |
| Teacher | Anyone who has already messaged the teacher; students with a session this year in a class section where the teacher is class teacher or subject teacher this session year; parents (father, mother or guardian) of those students | `TeacherApiController::canChatWith()`, `teacherClassSectionIds()` |

A refused student or parent send returns `You can not send a message to this user` (code 103); a refused teacher send returns `no_permission_message` with code 403. Nothing is saved in either case.

Notes:

- Students and parents can only start conversations with teachers; teachers can start conversations with students and parents. Students cannot message parents or other students, and parents cannot message each other.
- The Docusaurus feature page says students chat with their class teachers; the code also allows the student's subject teachers.
- The teacher's chat list for subject-teacher sections includes only the students who take the subject when it is an elective; `canChatWith()` itself does not check electives.

## Attachments and limits

| Setting (`settings.type`) | Default | Enforced on the server |
|---|---|---|
| `max_files_or_images_in_one_message` | 10 | Student and parent `send-message`: `file` must be an array of at most this many (minimum 1) |
| `max_file_size_in_bytes` | 10 | Despite the name, the value is in **MB** (the settings form says "in MB"). Student and parent `send-message`: each `file.*` at most `value x 1,000,000 / 1024` KB. |
| `max_characters_in_text_message` | 500 | Not checked by any `send-message`; only passed to the apps |
| `automatically_messages_removed_days` | 30 | Retention used by the daily clean-up |

The apps read these values from `GET /api/settings?type=app_settings` under `chat_settings` (`max_file_size_in_bytes` is converted to bytes there) and enforce them in the UI (`lib/data/models/chatSettings.dart`). The Docusaurus "Chat Message Constraints" page still says the app limits are in `lib/utils/constants.dart`; that file no longer holds chat limits.

The teacher `send-message` validates only `receiver_id`, `message` (required without `file`) and `file.*` (nullable): the count and size settings are not applied to teachers. For every role the PHP limits (`upload_max_filesize`, `post_max_size`, `max_file_uploads`) still apply; the chat settings form caps its inputs at the current `max_file_uploads` and `upload_max_filesize` values.

Storage:

- Files go to the `public` disk in a folder of their own: `chatfile/<random>/<cleaned original name>` (32 random characters for students and parents, 40 for teachers). The original name is reduced to safe characters (students/parents: `[A-Za-z0-9._-]`, at most the last 120 characters), so two uploads with the same name never collide and a name cannot leave `chatfile/`. The apps show the last path segment as the file name.
- The `RejectDangerousUploads` middleware on the `api` group refuses script and web-page files (by any dot-separated part of the name or by detected type) and sanitises SVGs before any controller runs. See [Security-Model](Security-Model).
- Files are served from `/storage/chatfile/...` without authentication; anyone with a URL can download the file.

## Read receipts

There are no per-message read receipts. Unread counts come from `read_messages`:

1. When A sends to B and no pointer (`modal_id` B, `user_id` A) exists, `send-message` creates one with `last_read_message_id` null.
2. B's unread count for A is the number of messages from A to B with an id above that pointer (all of them while it is null). If no pointer exists the count is 0.
3. When B opens the conversation the app calls `read-all-message` with `user_id` = A, which moves the pointer to A's newest message.

`total_unread_users` in `get-user-list` is the number of contacts with a non-zero unread count. Because `last_read_message_id` is a cascading foreign key, deleting the message it points at (for example by the clean-up) deletes the whole pointer row; it is recreated on the next message from that sender.

## Push notification on each message

After saving, every `send-message` calls `sendSimpleNotification([$receiver_id], $title, $body, 'chat', null, $userinfo)`:

- `title`: the sender's full name.
- `body`: the message text, or `<n> Files Received` when the message has only files.
- `sender_info` (in the FCM `data`, JSON-encoded): the sender's profile as the receiver's chat list would show it, including `last_message` and `unread_message`, and `isParent` (0 student, 1 parent) for student and parent senders.

No `notifications` row is stored for chat. The apps use `type == 'chat'` to update open chat screens and show a chat notification (`chatNotificationChannelKey` in the student/parent app). See [Notifications](Notifications).

## Admin chat settings

**System Settings > Chat Settings** in the admin panel (`SettingController`, view `settings/chat_setting.blade.php`, permission `chat-settings` for all three routes):

| Route | Method | Behaviour |
|---|---|---|
| `GET chat-settings` (`chat_setting.index`) | `chat_setting_index` | Shows the four settings above |
| `POST chat-settings-update` (`chat_setting.update`) | `chat_setting_update` | All four fields required; saved to `settings` |
| `POST delete-chat-messages` (`chat_message.delete`) | `delete_chat_messages` | `from_date`, `to_date` (must be after `from_date`); deletes every message (and its files) dated within the inclusive range, for all users |

There is no admin screen to read conversations.

## Message clean-up

[`ChatMessageCleanupService`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/ChatMessageCleanupService.php):

| Method | Deletes |
|---|---|
| `deleteOlderThanConfiguredDays()` | Messages whose `date` is before today minus `automatically_messages_removed_days` (default 30) |
| `deleteBetweenDates($from, $to)` | Messages whose `date` falls between the two dates, inclusive (the admin date-range delete) |
| `deleteMessagesWithFiles($messages)` | Deletes each message's files from the public disk, the `chat_files` rows and the message; returns the count |

[`app/Console/Commands/CleanupOldChatMessages.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Console/Commands/CleanupOldChatMessages.php) (`php artisan app:cleanup-old-chat-messages`) runs `deleteOlderThanConfiguredDays()`, logs `Cleaned up old chat messages` with the count, and prints it. [`routes/console.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/routes/console.php) schedules it `->daily()->withoutOverlapping()`, so the server needs Laravel's scheduler cron:

```bash
* * * * * cd /path/to/admin-panel && php artisan schedule:run >> /dev/null 2>&1
```

The old public URL `delete-chat-message/cron-job` was removed in commit `3005b52` (it deleted messages without a login); `SettingController::cron_job()` still exists but has no route. See [Scheduled-Tasks-and-Commands](Scheduled-Tasks-and-Commands).

## Demo mode

With `DEMO_MODE=true`, `DemoMiddleware` blocks non-GET requests except a list that includes `/api/{student,parent,teacher}/send-message` and `get-user-message`, so chat keeps working on a demo install.

## Tests

| Test | Checks |
|---|---|
| `StudentApiSecurityTest::test_chat_messages_only_go_to_the_students_teachers_and_files_get_safe_names` | The list shows only the student's teacher; sending to another teacher or an admin is refused; an upload named `../notes v1.txt` is stored as `chatfile/<32 chars>/notes_v1.txt` |
| `ParentApiSecurityTest::test_chat_messages_only_go_to_the_childs_teachers` | Sending to another child's teacher or another parent is refused; attachments get their own folder |
| `TeacherApiSecurityTest::test_chat_is_limited_to_own_students_and_parents_and_files_never_collide` | A teacher of another section is refused (403); two uploads with the same name are both kept |

## See also

- [Notifications](Notifications)
- [API-Student](API-Student), [API-Parent](API-Parent), [API-Teacher](API-Teacher)
- [Student-Parent-App](Student-Parent-App), [Teacher-App](Teacher-App)
- [Scheduled-Tasks-and-Commands](Scheduled-Tasks-and-Commands)
- [Security-Model](Security-Model)
