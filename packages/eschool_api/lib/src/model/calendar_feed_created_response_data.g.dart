// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'calendar_feed_created_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CalendarFeedCreatedResponseData _$CalendarFeedCreatedResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CalendarFeedCreatedResponseData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['id', 'learner_id', 'include_lessons', 'url'],
    );
    final val = CalendarFeedCreatedResponseData(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      includeLessons: $checkedConvert('include_lessons', (v) => v as bool),
      url: $checkedConvert('url', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'includeLessons': 'include_lessons',
  },
);

Map<String, dynamic> _$CalendarFeedCreatedResponseDataToJson(
  CalendarFeedCreatedResponseData instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'include_lessons': instance.includeLessons,
  'url': instance.url,
};
