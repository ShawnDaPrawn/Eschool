import 'dart:convert';

import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/data/models/chatUser.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

/// How notifications behave in the teacher app. The FCM and local
/// notification code itself is in eschool_notifications.
final NotificationConfig notificationConfig = NotificationConfig(
  basicChannelDescription:
      'Notification channel for announcements and other notifications.',
  typesNotCounted: [
    NotificationUtility.noticeboardNotificationType,
    NotificationUtility.classNoticeboardNotificationType,
    NotificationUtility.classSectionNoticeboardNotificationType,
    NotificationUtility.chatNotificaitonType,
  ],
  includeDataInPayload: false,
  onTap: onNotificationTap,
  backgroundHandler: onBackgroundMessage,
  chatSenderFromApi: ChatUser.fromJsonAPI,
  chatSenderFromMap: ChatUser.fromMap,
);

/// FCM calls this, in an isolate of its own, for pushes that arrive while
/// the app is in the background or closed.
@pragma('vm:entry-point')
Future<void> onBackgroundMessage(RemoteMessage remoteMessage) =>
    NotificationUtility.handleBackgroundMessage(
      remoteMessage,
      notificationConfig,
    );

/// Opens the screen a tapped notification is about, by its `data['type']`.
void onNotificationTap(String notificationType, Map<String, dynamic> data) {
  if (notificationType == NotificationUtility.customNotificationType) {
    UiUtils.rootNavigatorKey.currentState?.pushNamed(Routes.notifications);
  } else if (notificationType ==
      NotificationUtility.assignmentSubmissionNotificationType) {
    UiUtils.rootNavigatorKey.currentState?.pushNamed(Routes.assignments);
  } else if (notificationType == NotificationUtility.chatNotificaitonType) {
    //get off the route if already on it
    if (Routes.currentRoute == Routes.chatMessages) {
      UiUtils.rootNavigatorKey.currentState?.pop();
    }
    UiUtils.rootNavigatorKey.currentState?.pushNamed(
      Routes.chatMessages,
      arguments: ChatUser.fromJsonAPI(jsonDecode(data['sender_info'])),
    );
  }
}
