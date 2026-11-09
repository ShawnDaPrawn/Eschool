// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diagnostic_answer_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiagnosticAnswerInput _$DiagnosticAnswerInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DiagnosticAnswerInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['answer']);
  final val = DiagnosticAnswerInput(
    answer: $checkedConvert('answer', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$DiagnosticAnswerInputToJson(
  DiagnosticAnswerInput instance,
) => <String, dynamic>{'answer': instance.answer};
