// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_list_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestListItem _$WeeklyDigestListItemFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WeeklyDigestListItem',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'language',
        'week_start',
        'week_end',
        'title',
        'opened',
      ],
    );
    final val = WeeklyDigestListItem(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      language: $checkedConvert('language', (v) => v as String),
      weekStart: $checkedConvert('week_start', (v) => v as String),
      weekEnd: $checkedConvert('week_end', (v) => v as String),
      title: $checkedConvert('title', (v) => v as String),
      opened: $checkedConvert('opened', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'weekStart': 'week_start',
    'weekEnd': 'week_end',
  },
);

Map<String, dynamic> _$WeeklyDigestListItemToJson(
  WeeklyDigestListItem instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'language': instance.language,
  'week_start': instance.weekStart,
  'week_end': instance.weekEnd,
  'title': instance.title,
  'opened': instance.opened,
};
