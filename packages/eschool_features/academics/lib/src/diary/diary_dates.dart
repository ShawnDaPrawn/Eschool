import 'package:flutter/material.dart';

/// Diary days are calendar dates in the school's wall time, written
/// `YYYY-MM-DD`; reminders are `YYYY-MM-DD HH:mm` in the same wall time.
String diaryDay(DateTime date) =>
    '${date.year.toString().padLeft(4, '0')}-'
    '${date.month.toString().padLeft(2, '0')}-'
    '${date.day.toString().padLeft(2, '0')}';

DateTime diaryDate(DateTime date) => DateTime(date.year, date.month, date.day);

/// [days] calendar days after [date]. Calendar arithmetic, not a
/// `Duration`, so a daylight-saving change cannot shift the day.
DateTime diaryAddDays(DateTime date, int days) =>
    DateTime(date.year, date.month, date.day + days);

/// The Monday that starts [date]'s week.
DateTime diaryWeekStart(DateTime date) =>
    diaryAddDays(date, DateTime.monday - date.weekday);

String diaryTime(DateTime at) =>
    '${diaryDay(at)} ${at.hour.toString().padLeft(2, '0')}:'
    '${at.minute.toString().padLeft(2, '0')}';

/// Reads `YYYY-MM-DD HH:mm` (or `YYYY-MM-DD`) as a wall-clock moment.
DateTime? diaryParse(String? value) {
  if (value == null) return null;
  final match = RegExp(
    r'^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}):(\d{2}))?$',
  ).firstMatch(value);
  if (match == null) return null;
  int part(int i) => int.parse(match.group(i) ?? '0');
  return DateTime(part(1), part(2), part(3), part(4), part(5));
}

String diaryDayLabel(BuildContext context, DateTime date) =>
    MaterialLocalizations.of(context).formatFullDate(date);

String diaryShortDay(BuildContext context, DateTime date) =>
    MaterialLocalizations.of(context).formatShortMonthDay(date);

String diaryClock(BuildContext context, DateTime at) =>
    MaterialLocalizations.of(context).formatTimeOfDay(
      TimeOfDay.fromDateTime(at),
      alwaysUse24HourFormat:
          MediaQuery.maybeOf(context)?.alwaysUse24HourFormat ?? false,
    );

/// The narrow weekday name ("M") in the current locale.
String diaryWeekday(BuildContext context, DateTime date) {
  final names = MaterialLocalizations.of(context).narrowWeekdays;
  // narrowWeekdays starts on Sunday.
  return names[date.weekday % 7];
}
