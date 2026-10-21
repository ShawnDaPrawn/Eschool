import 'dart:async';
import 'dart:math';

import 'package:awesome_notifications/awesome_notifications.dart';
import 'package:eschool_notifications/src/chat_notification_data.dart';
import 'package:eschool_notifications/src/notification_config.dart';
import 'package:eschool_notifications/src/notification_preferences.dart';
import 'package:eschool_notifications/src/notification_platform.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

/// The Android channel chat messages are shown on.
const String chatNotificationChannelKey = 'chat_notifications_channel';

class ChatNotificationsUtils {
  static int? currentChattingUserId;

  static StreamController<ChatNotificationData> notificationStreamController =
      StreamController.broadcast();

  static void initialize() {
    //remove old data when initializing to remove terminated state data
    const NotificationPreferences().setBackgroundChatNotificationData(data: []);
    notificationStreamController = StreamController.broadcast();
  }

  static void dispose() {
    notificationStreamController.close();
  }

  //foreground chat notification handler
  static void addChatStreamAndShowNotification({
    required RemoteMessage message,
    required NotificationConfig config,
  }) {
    final chatNotification = ChatNotificationData.fromRemoteMessage(
      remoteMessage: message,
      config: config,
    );
    notificationStreamController.add(chatNotification);
    if (currentChattingUserId != chatNotification.fromUser.userId &&
        NotificationPlatform.isAndroid) {
      createChatNotification(chatData: chatNotification, message: message);
    }
  }

  static void addChatStreamValue({required ChatNotificationData chatData}) {
    notificationStreamController.add(chatData);
  }

  static Future<void> createChatNotification({
    required ChatNotificationData chatData,
    required RemoteMessage message,
  }) async {
    String title = '';
    String body = '';
    String type = '';
    String? image;

    if (message.notification != null) {
      title = message.notification?.title ?? '';
      body = message.notification?.body ?? '';
    } else {
      title = message.data['title'] ?? '';
      body = message.data['body'] ?? '';
    }
    type = message.data['type'] ?? '';
    image = message.data['image'];

    if (image == null) {
      await AwesomeNotifications().createNotification(
        content: NotificationContent(
          id: Random().nextInt(5000),
          groupKey: chatData.receivedMessage.senderId.toString(),
          title: chatData.fromUser.userName,
          body: body,
          wakeUpScreen: true,
          largeIcon: chatData.fromUser.profileUrl,
          payload: {'type': type, 'sender_info': message.data['sender_info']},
          channelKey: chatNotificationChannelKey,
          summary: title,
          notificationLayout: NotificationLayout.Messaging,
          category: NotificationCategory.Message,
        ),
      );
    } else {
      await AwesomeNotifications().createNotification(
        content: NotificationContent(
          id: Random().nextInt(5000),
          groupKey: chatData.receivedMessage.senderId.toString(),
          title: chatData.fromUser.userName,
          body: body,
          wakeUpScreen: true,
          bigPicture: image,
          payload: {'type': type, 'sender_info': message.data['sender_info']},
          largeIcon: chatData.fromUser.profileUrl,
          channelKey: chatNotificationChannelKey,
          summary: title,
          notificationLayout: NotificationLayout.Messaging,
          category: NotificationCategory.Message,
        ),
      );
    }
  }
}
