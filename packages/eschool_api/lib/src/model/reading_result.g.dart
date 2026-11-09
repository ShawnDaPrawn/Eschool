// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingResult _$ReadingResultFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ReadingResult', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['choice', 'correct', 'answer', 'explanation'],
      );
      final val = ReadingResult(
        choice: $checkedConvert('choice', (v) => (v as num).toInt()),
        correct: $checkedConvert('correct', (v) => v as bool),
        answer: $checkedConvert('answer', (v) => (v as num).toInt()),
        explanation: $checkedConvert('explanation', (v) => v as String?),
      );
      return val;
    });

Map<String, dynamic> _$ReadingResultToJson(ReadingResult instance) =>
    <String, dynamic>{
      'choice': instance.choice,
      'correct': instance.correct,
      'answer': instance.answer,
      'explanation': instance.explanation,
    };
