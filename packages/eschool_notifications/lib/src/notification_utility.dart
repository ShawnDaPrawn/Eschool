import 'dart:async';
import 'dart:math';

import 'package:awesome_notifications/awesome_notifications.dart';
import 'package:eschool_notifications/src/chat_notification_data.dart';
import 'package:eschool_notifications/src/chat_notifications_utils.dart';
import 'package:eschool_notifications/src/notification_config.dart';
import 'package:eschool_notifications/src/notification_platform.dart';
import 'package:eschool_notifications/src/notification_preferences.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:permission_handler/permission_handler.dart';

/// The Android channel general notifications are shown on.
const String notificationChannelKey = 'basic_channel';

/// Push notifications (FCM) and the local notifications that show them on
/// Android, shared by both apps. Each app calls [configure] once at start-up
/// with its [NotificationConfig]; everything else is the same in both.
///
/// Nothing here runs on the web: the portals have no push notifications yet.
// ignore: avoid_classes_with_only_static_members
class NotificationUtility {
  static String customNotificationType = 'custom';
  static String noticeboardNotificationType = 'noticeboard';
  static String classNoticeboardNotificationType = 'class';
  static String classSectionNoticeboardNotificationType = 'class_section';
  static String assignmentlNotificationType = 'assignment';
  static String assignmentSubmissionNotificationType = 'assignment_submission';
  static String onlineFeePaymentNotificationType = 'Online';
  static String attendenceNotificationType = 'attendance';
  static String chatNotificaitonType = 'chat';
  static String feePaymentDueType = 'fees-due';

  static NotificationConfig? _config;
  static SessionRepository? _session;

  /// The unread badge on the notification icons.
  static final ValueNotifier<int> unreadCount = ValueNotifier(0);

  static StreamSubscription<RemoteMessage>? openAppStreamSubscription;
  static StreamSubscription<RemoteMessage>? onMessageOpenAppStreamSubscription;

  static NotificationConfig get config {
    assert(_config != null, 'Call NotificationUtility.configure() first.');
    return _config!;
  }

  static void configure(
    NotificationConfig config, {
    required SessionRepository session,
  }) {
    _config = config;
    _session = session;
  }

  //to receive topic-based notifications
  static Future<void> subscribeOrUnsubscribeToNotificationTopics({
    required NotificationAudience audience,
    required bool isUnsubscribe,
  }) async {
    final SessionRepository? session = _session;
    if (!NotificationPlatform.isSupported || session == null) {
      return;
    }
    if (session.hasSubscribedToNotificationTopics && !isUnsubscribe) {
      return;
    } else {
      final topics = audience.topics;
      try {
        for (final topic in topics) {
          if (isUnsubscribe) {
            await FirebaseMessaging.instance.unsubscribeFromTopic(topic);
          } else {
            await FirebaseMessaging.instance.subscribeToTopic(topic);
          }
        }
        if (isUnsubscribe) {
          session.setHasSubscribedToNotificationTopics(false);
        } else {
          session.setHasSubscribedToNotificationTopics(true);
        }
      } catch (_) {}
    }
  }

  static Future<void> setUpNotificationService(
    BuildContext buildContext,
  ) async {
    if (!NotificationPlatform.isSupported) {
      return;
    }
    ChatNotificationsUtils.initialize();
    NotificationSettings notificationSettings = await FirebaseMessaging.instance
        .getNotificationSettings();

    //ask for permission if not already allowed
    if (notificationSettings.authorizationStatus ==
            AuthorizationStatus.notDetermined ||
        notificationSettings.authorizationStatus ==
            AuthorizationStatus.denied) {
      notificationSettings = await FirebaseMessaging.instance
          .requestPermission();
    }
    if (buildContext.mounted) {
      initNotificationListener(buildContext);
    }
  }

  static void initNotificationListener(BuildContext buildContext) {
    FirebaseMessaging.instance.setForegroundNotificationPresentationOptions(
      alert: true,
      badge: true,
      sound: true,
    );
    AwesomeNotifications().setListeners(
      onActionReceivedMethod: NotificationUtility.onActionReceivedMethod,
      onNotificationCreatedMethod:
          NotificationUtility.onNotificationCreatedMethod,
      onNotificationDisplayedMethod:
          NotificationUtility.onNotificationDisplayedMethod,
      onDismissActionReceivedMethod:
          NotificationUtility.onDismissActionReceivedMethod,
    );
    openAppStreamSubscription = FirebaseMessaging.onMessage.listen(
      foregroundMessageListener,
    );
    FirebaseMessaging.onBackgroundMessage(config.backgroundHandler);
    onMessageOpenAppStreamSubscription = FirebaseMessaging.onMessageOpenedApp
        .listen((remoteMessage) {
          if (buildContext.mounted) {
            onMessageOpenedAppListener(remoteMessage, buildContext);
          }
        });
  }

  static Future<void> foregroundMessageListener(
    RemoteMessage remoteMessage,
  ) async {
    if (!config.typesNotCounted.contains(remoteMessage.data['type'])) {
      const preferences = NotificationPreferences();
      final int oldCount = await preferences.getNotificationCount();
      unreadCount.value = oldCount + 1;
      await preferences.setNotificationCount(oldCount + 1);
    }
    if (remoteMessage.data['type'] == chatNotificaitonType) {
      ChatNotificationsUtils.addChatStreamAndShowNotification(
        message: remoteMessage,
        config: config,
      );
    } else {
      if (NotificationPlatform.isAndroid) {
        createLocalNotification(dimissable: true, message: remoteMessage);
      }
    }
  }

  static void onMessageOpenedAppListener(
    RemoteMessage remoteMessage,
    BuildContext buildContext,
  ) {
    config.onTap(remoteMessage.data['type'] ?? '', remoteMessage.data);
  }

  static Future<void> initializeAwesomeNotification() async {
    final NotificationConfig? appConfig = _config;
    if (!NotificationPlatform.isSupported || appConfig == null) {
      return;
    }
    await AwesomeNotifications()
        .initialize('resource://drawable/ic_launcher_foreground', [
          NotificationChannel(
            channelKey: notificationChannelKey,
            channelName: 'Basic notifications',
            channelDescription: appConfig.basicChannelDescription,
            vibrationPattern: highVibrationPattern,
            importance: NotificationImportance.High,
          ),
          NotificationChannel(
            channelKey: chatNotificationChannelKey,
            channelName: 'Chat notifications',
            channelDescription: 'Notification related to chat',
            vibrationPattern: highVibrationPattern,
            importance: NotificationImportance.High,
          ),
        ]);
  }

  static Future<bool> isLocalNotificationAllowed() async {
    if (!NotificationPlatform.isSupported) {
      return false;
    }
    const notificationPermission = Permission.notification;
    final status = await notificationPermission.status;
    return status.isGranted;
  }

  /// Use this method to detect when a new notification or a schedule is created
  static Future<void> onNotificationCreatedMethod(
    ReceivedNotification receivedNotification,
  ) async {
    // Your code goes here
  }

  /// Use this method to detect every time that a new notification is displayed
  static Future<void> onNotificationDisplayedMethod(
    ReceivedNotification receivedNotification,
  ) async {
    // Your code goes here
  }

  /// Use this method to detect if the user dismissed a notification
  static Future<void> onDismissActionReceivedMethod(
    ReceivedAction receivedAction,
  ) async {
    // Your code goes here
  }

  /// Use this method to detect when the user taps on a notification or action button
  static Future<void> onActionReceivedMethod(
    ReceivedAction receivedAction,
  ) async {
    if (NotificationPlatform.isAndroid) {
      config.onTap(
        (receivedAction.payload ?? {})['type'] ?? '',
        Map.from(receivedAction.payload ?? {}),
      );
    }
  }

  static Future<void> createLocalNotification({
    required bool dimissable,
    required RemoteMessage message,
    NotificationConfig? config,
  }) async {
    final NotificationConfig appConfig = config ?? NotificationUtility.config;
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

    final Map<String, String?> payload = {'type': type};
    if (appConfig.includeDataInPayload) {
      payload.addAll(message.data.cast<String, String?>());
    }

    if (image == null) {
      await AwesomeNotifications().createNotification(
        content: NotificationContent(
          id: Random().nextInt(5000),
          autoDismissible: dimissable,
          title: title,
          body: body,
          locked: !dimissable,
          wakeUpScreen: true,
          payload: payload,
          channelKey: notificationChannelKey,
          notificationLayout: NotificationLayout.BigText,
        ),
      );
    } else {
      await AwesomeNotifications().createNotification(
        content: NotificationContent(
          id: Random().nextInt(5000),
          autoDismissible: dimissable,
          title: title,
          body: body,
          locked: !dimissable,
          wakeUpScreen: true,
          bigPicture: image,
          payload: payload,
          channelKey: notificationChannelKey,
          notificationLayout: NotificationLayout.BigPicture,
        ),
      );
    }
  }

  //remove when logging out to prevent multi-listeners
  static void removeListener() {
    try {
      openAppStreamSubscription?.cancel();
      onMessageOpenAppStreamSubscription?.cancel();
      const NotificationPreferences().setNotificationCount(0);
      ChatNotificationsUtils.dispose();
    } catch (_) {}
  }

  /// What the app's background handler does with a push that arrives while
  /// the app is not in front. It runs in an isolate of its own, so it takes
  /// the app's [config] rather than reading [configure]'s.
  static Future<void> handleBackgroundMessage(
    RemoteMessage remoteMessage,
    NotificationConfig config,
  ) async {
    const preferences = NotificationPreferences();
    if (!config.typesNotCounted.contains(remoteMessage.data['type'])) {
      await Firebase.initializeApp();

      final int oldCount = await preferences.getNotificationCount();
      await preferences.setNotificationCount(oldCount + 1);
    }
    if (remoteMessage.data['type'] == chatNotificaitonType) {
      //background chat message storing
      final List<ChatNotificationData> oldList = await preferences
          .getBackgroundChatNotificationData(config: config);
      final messageChatData = ChatNotificationData.fromRemoteMessage(
        remoteMessage: remoteMessage,
        config: config,
      );
      oldList.add(messageChatData);
      preferences.setBackgroundChatNotificationData(data: oldList);
      if (NotificationPlatform.isAndroid) {
        ChatNotificationsUtils.createChatNotification(
          chatData: messageChatData,
          message: remoteMessage,
        );
      }
    } else {
      if (NotificationPlatform.isAndroid) {
        createLocalNotification(
          dimissable: true,
          message: remoteMessage,
          config: config,
        );
      }
    }
  }
}
