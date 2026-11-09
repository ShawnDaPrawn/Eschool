// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_progress.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingProgress _$ReadingProgressFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ReadingProgress',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'page',
        'furthest_page',
        'page_count',
        'minutes_read',
        'finished',
        'finished_on',
      ],
    );
    final val = ReadingProgress(
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      furthestPage: $checkedConvert('furthest_page', (v) => (v as num).toInt()),
      pageCount: $checkedConvert('page_count', (v) => (v as num?)?.toInt()),
      minutesRead: $checkedConvert('minutes_read', (v) => (v as num).toInt()),
      finished: $checkedConvert('finished', (v) => v as bool),
      finishedOn: $checkedConvert(
        'finished_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'furthestPage': 'furthest_page',
    'pageCount': 'page_count',
    'minutesRead': 'minutes_read',
    'finishedOn': 'finished_on',
  },
);

Map<String, dynamic> _$ReadingProgressToJson(ReadingProgress instance) =>
    <String, dynamic>{
      'page': instance.page,
      'furthest_page': instance.furthestPage,
      'page_count': instance.pageCount,
      'minutes_read': instance.minutesRead,
      'finished': instance.finished,
      'finished_on': instance.finishedOn?.toIso8601String(),
    };
