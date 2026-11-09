// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_mark_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeMarkInput _$PracticeMarkInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeMarkInput', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id', 'correct']);
      final val = PracticeMarkInput(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        correct: $checkedConvert('correct', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$PracticeMarkInputToJson(PracticeMarkInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'correct': instance.correct,
    };
