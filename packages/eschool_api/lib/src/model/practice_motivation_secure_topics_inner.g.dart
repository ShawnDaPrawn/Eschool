// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_motivation_secure_topics_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeMotivationSecureTopicsInner
_$PracticeMotivationSecureTopicsInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeMotivationSecureTopicsInner', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['topic_id', 'name', 'subject', 'secured_on'],
      );
      final val = PracticeMotivationSecureTopicsInner(
        topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        subject: $checkedConvert('subject', (v) => v as String),
        securedOn: $checkedConvert(
          'secured_on',
          (v) => DateTime.parse(v as String),
        ),
      );
      return val;
    }, fieldKeyMap: const {'topicId': 'topic_id', 'securedOn': 'secured_on'});

Map<String, dynamic> _$PracticeMotivationSecureTopicsInnerToJson(
  PracticeMotivationSecureTopicsInner instance,
) => <String, dynamic>{
  'topic_id': instance.topicId,
  'name': instance.name,
  'subject': instance.subject,
  'secured_on': instance.securedOn.toIso8601String(),
};
