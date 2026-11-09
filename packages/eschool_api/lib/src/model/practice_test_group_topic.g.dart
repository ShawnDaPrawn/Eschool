// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_group_topic.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestGroupTopic _$PracticeTestGroupTopicFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeTestGroupTopic', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['topic_id', 'name', 'correct', 'answered'],
  );
  final val = PracticeTestGroupTopic(
    topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    correct: $checkedConvert('correct', (v) => (v as num).toInt()),
    answered: $checkedConvert('answered', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'topicId': 'topic_id'});

Map<String, dynamic> _$PracticeTestGroupTopicToJson(
  PracticeTestGroupTopic instance,
) => <String, dynamic>{
  'topic_id': instance.topicId,
  'name': instance.name,
  'correct': instance.correct,
  'answered': instance.answered,
};
