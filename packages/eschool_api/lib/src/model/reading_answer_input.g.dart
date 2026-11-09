// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_answer_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingAnswerInput _$ReadingAnswerInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ReadingAnswerInput', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['choice']);
      final val = ReadingAnswerInput(
        choice: $checkedConvert('choice', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$ReadingAnswerInputToJson(ReadingAnswerInput instance) =>
    <String, dynamic>{'choice': instance.choice};
