// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_answer.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestAnswer _$PracticeTestAnswerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeTestAnswer', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['question_id', 'answer']);
      final val = PracticeTestAnswer(
        questionId: $checkedConvert('question_id', (v) => (v as num).toInt()),
        answer: $checkedConvert('answer', (v) => v as String?),
      );
      return val;
    }, fieldKeyMap: const {'questionId': 'question_id'});

Map<String, dynamic> _$PracticeTestAnswerToJson(PracticeTestAnswer instance) =>
    <String, dynamic>{
      'question_id': instance.questionId,
      'answer': instance.answer,
    };
