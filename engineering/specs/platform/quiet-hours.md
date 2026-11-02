# Quiet hours for push notifications

Action Plan 1.5, the server part. Owner role: Backend (notifications). Builds on [notification rules](notification-rules.md) and [push devices](push-devices.md).

A parent does not want a homework notice buzzing at 23:00. Each person can set quiet hours. While they run, their push notices wait and arrive when the hours end. Safety notices always come through.

## Rules

- **One setting per person**, for every school (`notification_quiet_hours`, global like `notification_preferences`). The setting has: on or off, a start and an end time (HH:MM), and an IANA time zone. If no time zone is given, the current school's is used, and failing that Africa/Johannesburg. An end time earlier than the start means the hours run overnight. A start equal to the end is refused.
- **Push only.** In-app notices still appear and emails are still sent. Only push messages wait.
- **Held, not dropped.** When a push job for a person in quiet hours runs, it queues a fresh job for the same delivery, delayed until the hours end, and sends nothing. The delivery stays `queued`, so waiting never uses up a retry. When the held job runs, the usual delivery checks run again: access, choices and device. A log line `notification_held_for_quiet_hours` records the delivery key, user id and release time.
- **Only on a real queue.** On the `sync` queue connection a delayed job would run at once, so pushes are sent as before, never held (and never lost). Production uses a real queue.
- **Never held.** Types marked `quiet_hours_exempt` in `config/notification_types.php`: safeguarding alerts, urgent school notices, absence alerts and new sign-in alerts. Legacy push names (for example `attendance`) count as their registered type.
- **Payload hints for the apps.**
  - Every push's data carries `channel`, the type's area (`comms`, `academics`, `attendance` and so on), for Android notification channels.
  - iOS pushes carry `apns.payload.aps.interruption-level`: `time-sensitive` for the exempt types, otherwise `active`.
  - iOS pushes also carry `thread-id`: the learner when the notice names one, otherwise the area, so notices group.

## Interfaces

API v2:
- `GET /api/v2/me/notification-quiet-hours` (`getMyQuietHours`): private, no-store.
- `PUT /api/v2/me/notification-quiet-hours` (`updateMyQuietHours`): takes `enabled`, `starts_at`, `ends_at` and an optional `timezone`.

The response also lists `exempt_types`. There are no ids: a person can only read and set their own.

## Acceptance tests (`Platform/QuietHoursTest`)

| Given | When | Then |
| --- | --- | --- |
| Quiet 21:00 to 06:30 (Johannesburg), 22:15 | a school notice's push job runs | nothing sent; a fresh job for the same delivery waits until 06:30; the row stays queued; at 06:30 it is sent once |
| The same hours, 23:00 | an absence alert is pushed; another parent with quiet hours off is pushed | both sent at once; neither person's setting changes the other's |
| Overnight and same-day windows, a UTC clock | the release time is worked out | held exactly inside each window, in the person's zone; exempt types never |
| A parent's app | reads and sets quiet hours; sends bad times, an equal end, an unknown zone, a non-boolean; has no token | its own row only; 422 for each bad request, nothing saved; 401 |
| An iOS device | a school notice and an absence alert are pushed | `channel` comms and attendance; interruption level active and time-sensitive |

## Not in this step

- The apps' quiet-hours screen, Android channel creation and iOS Focus handling. The payload hints are ready for them.
- Holding emails, and a digest of everything that waited.
- Per-school or per-child quiet hours.
