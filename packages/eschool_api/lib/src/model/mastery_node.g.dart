// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_node.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryNode _$MasteryNodeFromJson(Map<String, dynamic> json) => $checkedCreate(
  'MasteryNode',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'topic_key',
        'name',
        'grade_level_id',
        'grade_name',
      ],
    );
    final val = MasteryNode(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      topicKey: $checkedConvert('topic_key', (v) => v as String),
      name: $checkedConvert('name', (v) => v as String),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      gradeName: $checkedConvert('grade_name', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'topicKey': 'topic_key',
    'gradeLevelId': 'grade_level_id',
    'gradeName': 'grade_name',
  },
);

Map<String, dynamic> _$MasteryNodeToJson(MasteryNode instance) =>
    <String, dynamic>{
      'id': instance.id,
      'topic_key': instance.topicKey,
      'name': instance.name,
      'grade_level_id': instance.gradeLevelId,
      'grade_name': instance.gradeName,
    };
