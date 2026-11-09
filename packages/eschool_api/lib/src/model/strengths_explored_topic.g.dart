// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_explored_topic.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsExploredTopic _$StrengthsExploredTopicFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StrengthsExploredTopic', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['topic_id', 'topic']);
  final val = StrengthsExploredTopic(
    topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
    topic: $checkedConvert('topic', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'topicId': 'topic_id'});

Map<String, dynamic> _$StrengthsExploredTopicToJson(
  StrengthsExploredTopic instance,
) => <String, dynamic>{'topic_id': instance.topicId, 'topic': instance.topic};
