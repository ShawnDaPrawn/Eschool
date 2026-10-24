// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_topic.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumTopic _$CurriculumTopicFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CurriculumTopic',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'key',
            'term_id',
            'name',
            'sort_order',
            'week_from',
            'week_to',
          ],
        );
        final val = CurriculumTopic(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          key: $checkedConvert('key', (v) => v as String),
          termId: $checkedConvert('term_id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          sortOrder: $checkedConvert('sort_order', (v) => (v as num).toInt()),
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

Map<String, dynamic> _$CurriculumTopicToJson(CurriculumTopic instance) =>
    <String, dynamic>{
      'id': instance.id,
      'key': instance.key,
      'term_id': instance.termId,
      'name': instance.name,
      'sort_order': instance.sortOrder,
      'week_from': instance.weekFrom,
      'week_to': instance.weekTo,
    };
