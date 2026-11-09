// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'create_calendar_feed_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CreateCalendarFeedRequest _$CreateCalendarFeedRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CreateCalendarFeedRequest',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['learner_id']);
    final val = CreateCalendarFeedRequest(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      includeLessons: $checkedConvert(
        'include_lessons',
        (v) => v as bool? ?? false,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'includeLessons': 'include_lessons',
  },
);

Map<String, dynamic> _$CreateCalendarFeedRequestToJson(
  CreateCalendarFeedRequest instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'include_lessons': ?instance.includeLessons,
};
