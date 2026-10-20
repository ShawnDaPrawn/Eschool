import 'package:eschool_teacher/data/models/chatNotificationData.dart';
import 'package:eschool_teacher/utils/appLanguages.dart';
import 'package:eschool_teacher/utils/hiveBoxKeys.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

class SettingsRepository {
  Future<void> setCurrentLanguageCode(String value) async {
    Hive.box(settingsBoxKey).put(currentLanguageCodeKey, value);
  }

  String getCurrentLanguageCode() {
    return Hive.box(settingsBoxKey).get(currentLanguageCodeKey) ??
        defaultLanguageCode;
  }

  Future<void> setNotificationCount(int value) async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.reload();
    await prefs.setInt(notificationCountKey, value);
  }

  Future<int> getNotificationCount() async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.reload();
    return prefs.getInt(notificationCountKey) ?? 0;
  }

  Future<void> setBackgroundChatNotificationData({
    required List<ChatNotificationData> data,
  }) async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.reload();
    prefs.setStringList(
      chatNotificationsBackgroundItemKey,
      data.map((e) => e.toJson()).toList(),
    );
  }

  Future<List<ChatNotificationData>> getBackgroundChatNotificationData() async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.reload();
    return prefs
            .getStringList(chatNotificationsBackgroundItemKey)
            ?.map((e) => ChatNotificationData.fromJson(e))
            .toList() ??
        [];
  }
}
