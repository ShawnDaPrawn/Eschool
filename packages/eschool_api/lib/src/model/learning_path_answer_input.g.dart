// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_answer_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathAnswerInput _$LearningPathAnswerInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearningPathAnswerInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'answer']);
  final val = LearningPathAnswerInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    answer: $checkedConvert('answer', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$LearningPathAnswerInputToJson(
  LearningPathAnswerInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'answer': instance.answer,
};
