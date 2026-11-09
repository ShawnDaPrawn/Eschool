// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_result_topic.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestResultTopic _$PracticeTestResultTopicFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeTestResultTopic', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['topic_id', 'name', 'correct', 'answered', 'total'],
  );
  final val = PracticeTestResultTopic(
    topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    correct: $checkedConvert('correct', (v) => (v as num).toInt()),
    answered: $checkedConvert('answered', (v) => (v as num).toInt()),
    total: $checkedConvert('total', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'topicId': 'topic_id'});

Map<String, dynamic> _$PracticeTestResultTopicToJson(
  PracticeTestResultTopic instance,
) => <String, dynamic>{
  'topic_id': instance.topicId,
  'name': instance.name,
  'correct': instance.correct,
  'answered': instance.answered,
  'total': instance.total,
};
