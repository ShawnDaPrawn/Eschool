import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:math';

import 'package:awesome_notifications/awesome_notifications.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/data/models/chatNotificationData.dart';
import 'package:eschool_teacher/data/models/chatUser.dart';
import 'package:eschool_teacher/data/repositories/authRepository.dart';
import 'package:eschool_teacher/data/repositories/settingsRepository.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:eschool_teacher/utils/constants.dart';
import 'package:eschool_teacher/utils/notificationUtils/chatNotificationsUtils.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:permission_handler/permission_handler.dart';

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
  static List<String> notificaitonTypesToNotIncrementCount = [
    noticeboardNotificationType,
    classNoticeboardNotificationType,
    classSectionNoticeboardNotificationType,
    chatNotificaitonType,
  ];

  static StreamSubscription<RemoteMessage>? openAppStreamSubscription;
  static StreamSubscription<RemoteMessage>? onMessageOpenAppStreamSubscription;

  //to receive topic-based notifications
  static Future<void> subscribeOrUnsubscribeToNotificationTopics({
    required bool isUnsubscribe,
  }) async {
    if (AuthRepository.getHaSubscribedToNotificationTopics() &&
        !isUnsubscribe) {
      return;
    } else {
      final topics = [
        if (Platform.isAndroid) 'teachersAndroid',
        if (Platform.isIOS) 'teachersIOS',
        if (Platform.isAndroid) 'allAndroid',
        if (Platform.isIOS) 'allIOS',
      ];
      try {
        for (final topic in topics) {
          if (isUnsubscribe) {
            await FirebaseMessaging.instance.unsubscribeFromTopic(topic);
          } else {
            await FirebaseMessaging.instance.subscribeToTopic(topic);
          }
        }
        if (isUnsubscribe) {
          AuthRepository.setHaSubscribedToNotificationTopics(false);
        } else {
          AuthRepository.setHaSubscribedToNotificationTopics(true);
        }
      } catch (_) {}
    }
  }

  static Future<void> setUpNotificationService(
    BuildContext buildContext,
  ) async {
    ChatNotificationsUtils.initialize();
    NotificationSettings notificationSettings = await FirebaseMessaging.instance
        .getNotificationSettings();

    //ask for permission
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
    FirebaseMessaging.onBackgroundMessage(onBackgroundMessage);
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
    if (!notificaitonTypesToNotIncrementCount.contains(
      remoteMessage.data['type'],
    )) {
      final int oldCount = await SettingsRepository().getNotificationCount();
      notificationCountValueNotifier.value = oldCount + 1;
      await SettingsRepository().setNotificationCount(oldCount + 1);
    }
    if (remoteMessage.data['type'] == chatNotificaitonType) {
      ChatNotificationsUtils.addChatStreamAndShowNotification(
        message: remoteMessage,
      );
    } else {
      if (Platform.isAndroid) {
        createLocalNotification(dimissable: true, message: remoteMessage);
      }
    }
  }

  static void onMessageOpenedAppListener(
    RemoteMessage remoteMessage,
    BuildContext buildContext,
  ) {
    _onTapNotificationScreenNavigateCallback(
      remoteMessage.data['type'] ?? '',
      remoteMessage.data,
    );
  }

  static void _onTapNotificationScreenNavigateCallback(
    String notificationType,
    Map<String, dynamic> data,
  ) {
    if (notificationType == customNotificationType) {
      UiUtils.rootNavigatorKey.currentState?.pushNamed(Routes.notifications);
    } else if (notificationType == assignmentSubmissionNotificationType) {
      UiUtils.rootNavigatorKey.currentState?.pushNamed(Routes.assignments);
    } else if (notificationType == chatNotificaitonType) {
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

  static Future<void> initializeAwesomeNotification() async {
    await AwesomeNotifications().initialize(
      'resource://drawable/ic_launcher_foreground',
      [
        NotificationChannel(
          channelKey: notificationChannelKey,
          channelName: 'Basic notifications',
          channelDescription:
              'Notification channel for announcements and other notifications.',
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
      ],
    );
  }

  static Future<bool> isLocalNotificationAllowed() async {
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
    if (Platform.isAndroid) {
      _onTapNotificationScreenNavigateCallback(
        (receivedAction.payload ?? {})['type'] ?? '',
        Map.from(receivedAction.payload ?? {}),
      );
    }
  }

  static Future<void> createLocalNotification({
    required bool dimissable,
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
          autoDismissible: dimissable,
          title: title,
          body: body,
          locked: !dimissable,
          wakeUpScreen: true,
          payload: {'type': type},
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
          payload: {'type': type},
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
      SettingsRepository().setNotificationCount(0);
      ChatNotificationsUtils.dispose();
    } catch (_) {}
  }
}

@pragma('vm:entry-point')
Future<void> onBackgroundMessage(RemoteMessage remoteMessage) async {
  if (!NotificationUtility.notificaitonTypesToNotIncrementCount.contains(
    remoteMessage.data['type'],
  )) {
    await Firebase.initializeApp();

    final int oldCount = await SettingsRepository().getNotificationCount();
    await SettingsRepository().setNotificationCount(oldCount + 1);
  }
  if (remoteMessage.data['type'] == NotificationUtility.chatNotificaitonType) {
    //background chat message storing
    final List<ChatNotificationData> oldList = await SettingsRepository()
        .getBackgroundChatNotificationData();
    final messageChatData = ChatNotificationData.fromRemoteMessage(
      remoteMessage: remoteMessage,
    );
    oldList.add(messageChatData);
    SettingsRepository().setBackgroundChatNotificationData(data: oldList);
    if (Platform.isAndroid) {
      ChatNotificationsUtils.createChatNotification(
        chatData: messageChatData,
        message: remoteMessage,
      );
    }
  } else {
    if (Platform.isAndroid) {
      NotificationUtility.createLocalNotification(
        dimissable: true,
        message: remoteMessage,
      );
    }
  }
}
