// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_topic.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathTopic _$LearningPathTopicFromJson(Map<String, dynamic> json) =>
    $checkedCreate('LearningPathTopic', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = LearningPathTopic(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$LearningPathTopicToJson(LearningPathTopic instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
