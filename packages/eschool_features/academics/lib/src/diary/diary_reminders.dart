import 'package:eschool_api/eschool_api.dart';

import 'diary_dates.dart';

/// One reminder on this device, for an entry or a school item.
class DiaryReminder {
  const DiaryReminder({required this.at, required this.text});

  /// Wall-clock time on this device.
  final DateTime at;
  final String text;
}

/// Schedules the learner's diary reminders as notifications on this device.
/// The app supplies it; the diary works without one.
abstract interface class DiaryReminders {
  /// For each key (`entry:12`, `homework:5`): schedule its reminder,
  /// replacing an earlier one with the same key, or cancel it when null.
  Future<void> plan(
    int schoolId,
    int readerId,
    Map<String, DiaryReminder?> reminders,
  );
}

String diaryEntryKey(int id) => 'entry:$id';
String diaryItemKey(DiarySchoolItem item) => '${item.source_.value}:${item.id}';

/// The reminders the loaded entries and items ask for: a future reminder on
/// something not yet done, otherwise none.
Map<String, DiaryReminder?> diaryReminderPlan(
  Iterable<DiaryEntry> entries,
  Iterable<DiarySchoolItem> items,
  DateTime now,
) {
  DiaryReminder? remind(String? at, bool done, String text) {
    final when = diaryParse(at);
    return when == null || done || !when.isAfter(now)
        ? null
        : DiaryReminder(at: when, text: text);
  }

  return {
    for (final entry in entries)
      diaryEntryKey(entry.id): remind(entry.remindAt, entry.done, entry.title),
    for (final item in items)
      diaryItemKey(item): remind(
        item.state.remindAt,
        item.state.done,
        item.title,
      ),
  };
}
