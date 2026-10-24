// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_topic.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentTopic _$ContentTopicFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ContentTopic', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = ContentTopic(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$ContentTopicToJson(ContentTopic instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
