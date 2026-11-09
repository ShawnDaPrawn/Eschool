// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_week.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingWeek _$ReadingWeekFromJson(Map<String, dynamic> json) => $checkedCreate(
  'ReadingWeek',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'week_start',
        'minutes_this_week',
        'days_this_week',
        'books_finished',
      ],
    );
    final val = ReadingWeek(
      weekStart: $checkedConvert(
        'week_start',
        (v) => DateTime.parse(v as String),
      ),
      minutesThisWeek: $checkedConvert(
        'minutes_this_week',
        (v) => (v as num).toInt(),
      ),
      daysThisWeek: $checkedConvert(
        'days_this_week',
        (v) => (v as num).toInt(),
      ),
      booksFinished: $checkedConvert(
        'books_finished',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'weekStart': 'week_start',
    'minutesThisWeek': 'minutes_this_week',
    'daysThisWeek': 'days_this_week',
    'booksFinished': 'books_finished',
  },
);

Map<String, dynamic> _$ReadingWeekToJson(ReadingWeek instance) =>
    <String, dynamic>{
      'week_start': instance.weekStart.toIso8601String(),
      'minutes_this_week': instance.minutesThisWeek,
      'days_this_week': instance.daysThisWeek,
      'books_finished': instance.booksFinished,
    };
