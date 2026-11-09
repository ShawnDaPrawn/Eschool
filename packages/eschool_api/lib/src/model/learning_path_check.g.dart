// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_check.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathCheck _$LearningPathCheckFromJson(Map<String, dynamic> json) =>
    $checkedCreate('LearningPathCheck', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['correct', 'total', 'needed', 'passed'],
      );
      final val = LearningPathCheck(
        correct: $checkedConvert('correct', (v) => (v as num).toInt()),
        total: $checkedConvert('total', (v) => (v as num).toInt()),
        needed: $checkedConvert('needed', (v) => (v as num).toInt()),
        passed: $checkedConvert('passed', (v) => v as bool),
      );
      return val;
    });

Map<String, dynamic> _$LearningPathCheckToJson(LearningPathCheck instance) =>
    <String, dynamic>{
      'correct': instance.correct,
      'total': instance.total,
      'needed': instance.needed,
      'passed': instance.passed,
    };
