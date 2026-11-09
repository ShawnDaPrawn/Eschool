// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_quick_quiz_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeQuickQuizInput _$PracticeQuickQuizInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeQuickQuizInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'topic_ids', 'question_count'],
    );
    final val = PracticeQuickQuizInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      topicIds: $checkedConvert(
        'topic_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
      ),
      questionCount: $checkedConvert(
        'question_count',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'topicIds': 'topic_ids',
    'questionCount': 'question_count',
  },
);

Map<String, dynamic> _$PracticeQuickQuizInputToJson(
  PracticeQuickQuizInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'topic_ids': instance.topicIds.toList(),
  'question_count': instance.questionCount,
};
