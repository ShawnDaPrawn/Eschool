// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_topic_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumTopicWrite _$CurriculumTopicWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CurriculumTopicWrite',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['term_id', 'name']);
    final val = CurriculumTopicWrite(
      key: $checkedConvert('key', (v) => v as String?),
      termId: $checkedConvert('term_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      sortOrder: $checkedConvert('sort_order', (v) => (v as num?)?.toInt()),
      weekFrom: $checkedConvert('week_from', (v) => (v as num?)?.toInt()),
      weekTo: $checkedConvert('week_to', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'termId': 'term_id',
    'sortOrder': 'sort_order',
    'weekFrom': 'week_from',
    'weekTo': 'week_to',
  },
);

Map<String, dynamic> _$CurriculumTopicWriteToJson(
  CurriculumTopicWrite instance,
) => <String, dynamic>{
  'key': ?instance.key,
  'term_id': instance.termId,
  'name': instance.name,
  'sort_order': ?instance.sortOrder,
  'week_from': ?instance.weekFrom,
  'week_to': ?instance.weekTo,
};
