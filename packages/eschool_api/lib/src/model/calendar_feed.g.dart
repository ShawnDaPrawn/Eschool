// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'calendar_feed.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CalendarFeed _$CalendarFeedFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CalendarFeed',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'learner_id',
            'learner_name',
            'include_lessons',
            'created_at',
            'last_used_at',
          ],
        );
        final val = CalendarFeed(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          learnerName: $checkedConvert('learner_name', (v) => v as String),
          includeLessons: $checkedConvert('include_lessons', (v) => v as bool),
          createdAt: $checkedConvert(
            'created_at',
            (v) => DateTime.parse(v as String),
          ),
          lastUsedAt: $checkedConvert(
            'last_used_at',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'learnerName': 'learner_name',
        'includeLessons': 'include_lessons',
        'createdAt': 'created_at',
        'lastUsedAt': 'last_used_at',
      },
    );

Map<String, dynamic> _$CalendarFeedToJson(CalendarFeed instance) =>
    <String, dynamic>{
      'id': instance.id,
      'learner_id': instance.learnerId,
      'learner_name': instance.learnerName,
      'include_lessons': instance.includeLessons,
      'created_at': instance.createdAt.toIso8601String(),
      'last_used_at': instance.lastUsedAt?.toIso8601String(),
    };
