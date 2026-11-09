// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_topic.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsTopic _$StrengthsTopicFromJson(Map<String, dynamic> json) =>
    $checkedCreate('StrengthsTopic', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['topic_id', 'topic', 'subject_id', 'subject'],
      );
      final val = StrengthsTopic(
        topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
        topic: $checkedConvert('topic', (v) => v as String),
        subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
        subject: $checkedConvert('subject', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'topicId': 'topic_id', 'subjectId': 'subject_id'});

Map<String, dynamic> _$StrengthsTopicToJson(StrengthsTopic instance) =>
    <String, dynamic>{
      'topic_id': instance.topicId,
      'topic': instance.topic,
      'subject_id': instance.subjectId,
      'subject': instance.subject,
    };
