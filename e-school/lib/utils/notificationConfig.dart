import 'dart:convert';

import 'package:eschool/app/routes.dart';
import 'package:eschool/data/models/chatUser.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

/// How notifications behave in the learner and parent app. The FCM and
/// local notification code itself is in eschool_notifications.
final NotificationConfig notificationConfig = NotificationConfig(
  basicChannelDescription: 'General notification channel',
  typesNotCounted: [
    NotificationUtility.noticeboardNotificationType,
    NotificationUtility.classNoticeboardNotificationType,
    NotificationUtility.classSectionNoticeboardNotificationType,
    NotificationUtility.chatNotificaitonType,
    NotificationUtility.feePaymentDueType,
  ],
  includeDataInPayload: true,
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
  if (notificationType == NotificationUtility.noticeboardNotificationType) {
    UiUtils.rootNavigatorKey.currentState?.pushNamed(Routes.noticeBoard);
  } else if (notificationType ==
      NotificationUtility.assignmentlNotificationType) {
    HomeScreen.homeScreenKey.currentState?.navigateToAssignmentContainer();
  } else if (notificationType == NotificationUtility.customNotificationType) {
    UiUtils.rootNavigatorKey.currentState?.pushNamed(Routes.notifications);
  } else if (notificationType ==
      NotificationUtility.onlineFeePaymentNotificationType) {
    UiUtils.rootNavigatorKey.currentState?.pushNamed(Routes.feesTransaction);
  } else if (notificationType == NotificationUtility.feePaymentDueType) {
    UiUtils.rootNavigatorKey.currentState?.pushNamed(
      Routes.feesDetails,
      arguments: {'childId': data['child_id']},
    );
  } else if (notificationType == NotificationUtility.chatNotificaitonType) {
    //get off the route if already on it
    if (Routes.currentRoute == Routes.chatMessages) {
      UiUtils.rootNavigatorKey.currentState?.pop();
    }
    UiUtils.rootNavigatorKey.currentState?.pushNamed(
      Routes.chatMessages,
      arguments: {
        'chatUser': ChatUser.fromJsonAPI(jsonDecode(data['sender_info'])),
      },
    );
  }
}
