import 'package:awesome_notifications/awesome_notifications.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_notifications/eschool_notifications.dart';

/// The learner diary's reminders, as notifications scheduled on this phone.
/// They use their own channel, whose notifications keep their text off a
/// locked screen. Each sync replaces them all with the server's list of
/// upcoming reminders; signing out cancels them all.
class DeviceDiaryReminders implements DiaryPhoneReminders {
  const DeviceDiaryReminders();

  /// The notification type a tapped reminder carries, to open the diary.
  static const String notificationType = 'diary';

  // Bumped by [clear]: a sync that started earlier stops scheduling.
  static int _generation = 0;
  // One sync or clear at a time, in the order they were asked for.
  static Future<void> _queue = Future<void>.value();

  /// A stable id per account, school and reminder key, clear of the small
  /// random ids other local notifications use.
  static int notificationId(int schoolId, int readerId, String key) {
    var hash = 0x811c9dc5;
    for (final unit in '$schoolId:$readerId:$key'.codeUnits) {
      hash = ((hash ^ unit) * 0x01000193) & 0xffffffff;
    }
    return 0x10000000 + (hash % 0x60000000);
  }

  static Future<void> _enqueue(Future<void> Function() work) =>
      _queue = _queue.then((_) => work()).catchError((Object _) {});

  @override
  Future<void> replaceAll(
    int schoolId,
    int readerId,
    List<DiaryReminder> reminders,
  ) {
    final generation = _generation;
    return _enqueue(() async {
      if (!NotificationUtility.isActive || generation != _generation) return;
      final notifications = AwesomeNotifications();
      await notifications.cancelSchedulesByChannelKey(diaryReminderChannelKey);
      final now = DateTime.now().toUtc();
      for (final reminder in reminders) {
        if (generation != _generation) break;
        final at = reminder.at.toUtc();
        if (!at.isAfter(now)) continue;
        await notifications.createNotification(
          content: NotificationContent(
            id: notificationId(schoolId, readerId, reminder.key),
            channelKey: diaryReminderChannelKey,
            title: 'Diary reminder',
            body: reminder.text,
            category: NotificationCategory.Reminder,
            wakeUpScreen: true,
            payload: {'type': notificationType},
          ),
          schedule: NotificationCalendar.fromDate(
            date: at,
            allowWhileIdle: true,
          ),
        );
      }
      // Signed out while scheduling: take back what this sync created.
      if (generation != _generation) {
        await notifications.cancelSchedulesByChannelKey(
          diaryReminderChannelKey,
        );
      }
    });
  }

  /// Cancels every diary reminder on this phone, shown or still to come,
  /// and stops any sync on its way.
  static Future<void> clear() {
    _generation++;
    return _enqueue(() async {
      if (!NotificationUtility.isActive) return;
      final notifications = AwesomeNotifications();
      await notifications.cancelSchedulesByChannelKey(diaryReminderChannelKey);
      await notifications.cancelNotificationsByChannelKey(
        diaryReminderChannelKey,
      );
    });
  }
}
