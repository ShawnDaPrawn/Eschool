// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_memory_topic.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorMemoryTopic _$TutorMemoryTopicFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TutorMemoryTopic', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = TutorMemoryTopic(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$TutorMemoryTopicToJson(TutorMemoryTopic instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
