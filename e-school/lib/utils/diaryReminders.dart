import 'package:awesome_notifications/awesome_notifications.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_notifications/eschool_notifications.dart';

/// The learner diary's reminders, as notifications scheduled on this phone.
/// They use their own channel, whose notifications keep their text off a
/// locked screen. Signing out cancels them all.
class DeviceDiaryReminders implements DiaryReminders {
  const DeviceDiaryReminders();

  /// The notification type a tapped reminder carries, to open the diary.
  static const String notificationType = 'diary';

  /// A stable id per account, school and entry, clear of the small random
  /// ids other local notifications use.
  static int notificationId(int schoolId, int readerId, String key) {
    var hash = 0x811c9dc5;
    for (final unit in '$schoolId:$readerId:$key'.codeUnits) {
      hash = ((hash ^ unit) * 0x01000193) & 0xffffffff;
    }
    return 0x10000000 + (hash % 0x60000000);
  }

  @override
  Future<void> plan(
    int schoolId,
    int readerId,
    Map<String, DiaryReminder?> reminders,
  ) async {
    if (!NotificationUtility.isActive) return;
    final notifications = AwesomeNotifications();
    for (final MapEntry(:key, :value) in reminders.entries) {
      final id = notificationId(schoolId, readerId, key);
      await notifications.cancel(id);
      if (value == null) continue;
      await notifications.createNotification(
        content: NotificationContent(
          id: id,
          channelKey: diaryReminderChannelKey,
          title: 'Diary reminder',
          body: value.text,
          category: NotificationCategory.Reminder,
          wakeUpScreen: true,
          payload: {'type': notificationType},
        ),
        schedule: NotificationCalendar.fromDate(
          date: value.at,
          allowWhileIdle: true,
        ),
      );
    }
  }

  /// Cancels every diary reminder on this phone, shown or still to come.
  static Future<void> clear() async {
    if (!NotificationUtility.isActive) return;
    try {
      final notifications = AwesomeNotifications();
      await notifications.cancelSchedulesByChannelKey(diaryReminderChannelKey);
      await notifications.cancelNotificationsByChannelKey(
        diaryReminderChannelKey,
      );
    } catch (_) {}
  }
}
