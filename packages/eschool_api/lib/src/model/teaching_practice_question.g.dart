// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_practice_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingPracticeQuestion _$TeachingPracticeQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TeachingPracticeQuestion',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['topic_id', 'estimated_seconds', 'definition'],
    );
    final val = TeachingPracticeQuestion(
      topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
      estimatedSeconds: $checkedConvert(
        'estimated_seconds',
        (v) => (v as num).toInt(),
      ),
      definition: $checkedConvert(
        'definition',
        (v) => PracticeQuestion.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'topicId': 'topic_id',
    'estimatedSeconds': 'estimated_seconds',
  },
);

Map<String, dynamic> _$TeachingPracticeQuestionToJson(
  TeachingPracticeQuestion instance,
) => <String, dynamic>{
  'topic_id': instance.topicId,
  'estimated_seconds': instance.estimatedSeconds,
  'definition': instance.definition.toJson(),
};
