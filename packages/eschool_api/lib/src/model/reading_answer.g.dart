// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_answer.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingAnswer _$ReadingAnswerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ReadingAnswer', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'question_id',
          'choice',
          'correct',
          'answer',
          'explanation',
        ],
      );
      final val = ReadingAnswer(
        questionId: $checkedConvert('question_id', (v) => (v as num).toInt()),
        choice: $checkedConvert('choice', (v) => (v as num).toInt()),
        correct: $checkedConvert('correct', (v) => v as bool),
        answer: $checkedConvert('answer', (v) => (v as num).toInt()),
        explanation: $checkedConvert('explanation', (v) => v as String?),
      );
      return val;
    }, fieldKeyMap: const {'questionId': 'question_id'});

Map<String, dynamic> _$ReadingAnswerToJson(ReadingAnswer instance) =>
    <String, dynamic>{
      'question_id': instance.questionId,
      'choice': instance.choice,
      'correct': instance.correct,
      'answer': instance.answer,
      'explanation': instance.explanation,
    };
