# eschool_notifications

Push notifications (Firebase Cloud Messaging) and the local notifications
that show them, shared by both NavigateEd apps. Depend on it by path and
import the one library:

```yaml
dependencies:
  eschool_notifications:
    path: ../packages/eschool_notifications
```

```dart
import 'package:eschool_notifications/eschool_notifications.dart';
```

## How an app uses it

Each app describes what differs in a top-level `NotificationConfig` in
`lib/utils/notificationConfig.dart`, and calls
`NotificationUtility.configure(notificationConfig, session: Api.session)` in
`initializeApp()` before `initializeAwesomeNotification()`:

| Config field | Learner app | Teacher app |
|---|---|---|
| `basicChannelDescription` | General notification channel | Notification channel for announcements and other notifications. |
| `typesNotCounted` (no badge) | noticeboard, class, class_section, chat, fees-due | noticeboard, class, class_section, chat |
| `includeDataInPayload` | yes (fee reminders need `child_id`) | no |
| `onTap` | `onNotificationTap`: routes by `data['type']` | same, its own routes |
| `backgroundHandler` | the app's `@pragma('vm:entry-point') onBackgroundMessage`, which calls `NotificationUtility.handleBackgroundMessage` with the config | same |
| `chatSenderFromApi` / `chatSenderFromMap` | `ChatUser.fromJsonAPI` / `ChatUser.fromMap` | same |

Topics are named by audience (`NotificationAudience.students`, `parents`,
`teachers`): a device subscribes to `<audience><Platform>` and
`all<Platform>`, for example `parentsAndroid` and `allAndroid`, the names the
admin panel's `sendNotificationToTopic()` uses. The badge count
(`notificationCount`) and chat messages that arrived in the background
(`chatNotificationsBackgroundItem`) stay in SharedPreferences under the same
keys as before; `NotificationUtility.unreadCount` is the badge.

Nothing runs on the web (`NotificationPlatform.isSupported` is false there):
the portals have no push notifications in Phase 0 and list notifications from
the API instead. Web push needs a Firebase web app and a VAPID key, and the
server would have to send to each browser's token, because FCM topics cannot
be subscribed from a browser.

`context.changed` is a silent push: nothing is shown, and
`NotificationUtility.onContextChanged` tells the app to fetch `/me/context`
again; one that arrives in the background is noted in SharedPreferences
(`contextChanged`) and taken with `NotificationPreferences.takeContextChanged()`
when the app is back in front.

## Notifications settings

`NotificationSettingsScreen` lists each notification type the server names
for the user, with a switch per channel it offers (push, email, in-app); a
channel the school has made mandatory shows a lock and the school's reason.
It reads and writes through `NotificationSettingsRepository`;
`ApiNotificationSettingsRepository` calls `GET` and `PUT
/api/v2/me/notification-preferences` on the app's `ApiClient`, and tests pass a
fake. `NotificationSettingsButton` is the gear for the Notifications screen's
header, shown once the server answers the endpoint. The models are written by
hand until the endpoint is in `openapi.yaml`.

## Checks

```sh
flutter pub get
flutter analyze --no-fatal-infos
flutter test
```
