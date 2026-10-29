import 'package:eschool_api/eschool_api.dart';

/// Schedules the learner's diary reminders as notifications on this phone.
/// The app supplies it; the diary works without one.
abstract interface class DiaryPhoneReminders {
  /// Replaces every diary reminder scheduled on this phone with [reminders]
  /// (the server's complete list of upcoming ones, [DiaryReminder.at] in UTC).
  Future<void> replaceAll(
    int schoolId,
    int readerId,
    List<DiaryReminder> reminders,
  );
}

/// The key a school item's reminder has (`homework:5`), as the server sends it.
String diaryItemKey(DiarySchoolItem item) => '${item.source_.value}:${item.id}';
