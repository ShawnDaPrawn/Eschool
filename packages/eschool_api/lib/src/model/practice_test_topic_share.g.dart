// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_topic_share.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestTopicShare _$PracticeTestTopicShareFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeTestTopicShare', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['topic_id', 'weight', 'questions']);
  final val = PracticeTestTopicShare(
    topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
    weight: $checkedConvert('weight', (v) => (v as num).toInt()),
    questions: $checkedConvert('questions', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'topicId': 'topic_id'});

Map<String, dynamic> _$PracticeTestTopicShareToJson(
  PracticeTestTopicShare instance,
) => <String, dynamic>{
  'topic_id': instance.topicId,
  'weight': instance.weight,
  'questions': instance.questions,
};
