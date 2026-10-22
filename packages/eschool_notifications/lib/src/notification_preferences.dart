import 'package:eschool_notifications/src/chat_notification_data.dart';
import 'package:eschool_notifications/src/notification_config.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// The unread badge count and the chat messages that arrived while the app
/// was in the background, in SharedPreferences under the keys both apps have
/// always used. The background handler writes them from its own isolate, so
/// every read and write reloads first.
class NotificationPreferences {
  const NotificationPreferences();

  static const String notificationCountKey = 'notificationCount';
  static const String chatNotificationsBackgroundItemKey =
      'chatNotificationsBackgroundItem';

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

  Future<List<ChatNotificationData>> getBackgroundChatNotificationData({
    required NotificationConfig config,
  }) async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.reload();
    return prefs
            .getStringList(chatNotificationsBackgroundItemKey)
            ?.map((e) => ChatNotificationData.fromJson(e, config: config))
            .toList() ??
        [];
  }
}
