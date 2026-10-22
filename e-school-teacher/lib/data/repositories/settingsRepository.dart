import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool_teacher/utils/notificationConfig.dart';
import 'package:eschool_teacher/utils/appLanguages.dart';
import 'package:eschool_teacher/utils/hiveBoxKeys.dart';
import 'package:hive_flutter/hive_flutter.dart';

class SettingsRepository {
  Future<void> setCurrentLanguageCode(String value) async {
    Hive.box(settingsBoxKey).put(currentLanguageCodeKey, value);
  }

  String getCurrentLanguageCode() {
    return Hive.box(settingsBoxKey).get(currentLanguageCodeKey) ??
        defaultLanguageCode;
  }

  Future<void> setNotificationCount(int value) =>
      const NotificationPreferences().setNotificationCount(value);

  Future<int> getNotificationCount() =>
      const NotificationPreferences().getNotificationCount();

  Future<void> setBackgroundChatNotificationData({
    required List<ChatNotificationData> data,
  }) => const NotificationPreferences().setBackgroundChatNotificationData(
    data: data,
  );

  Future<List<ChatNotificationData>> getBackgroundChatNotificationData() =>
      const NotificationPreferences().getBackgroundChatNotificationData(
        config: notificationConfig,
      );
}
