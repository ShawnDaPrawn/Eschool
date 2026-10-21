import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool/utils/notificationConfig.dart';
import 'package:eschool/utils/appLanguages.dart';
import 'package:eschool/utils/hiveBoxKeys.dart';
import 'package:hive/hive.dart';

class SettingsRepository {
  Future<void> setCurrentLanguageCode(String value) async {
    Hive.box(settingsBoxKey).put(currentLanguageCodeKey, value);
  }

  Future<void> setAllowNotification(bool value) async {
    Hive.box(settingsBoxKey).put(allowNotificationKey, value);
  }

  String getCurrentLanguageCode() {
    return Hive.box(settingsBoxKey).get(currentLanguageCodeKey) ??
        defaultLanguageCode;
  }

  bool getAllowNotification() {
    return Hive.box(settingsBoxKey).get(allowNotificationKey) ?? true;
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
