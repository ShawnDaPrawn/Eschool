// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diagnostic_question_options_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiagnosticQuestionOptionsInner _$DiagnosticQuestionOptionsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DiagnosticQuestionOptionsInner', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'text']);
  final val = DiagnosticQuestionOptionsInner(
    id: $checkedConvert('id', (v) => v as String),
    text: $checkedConvert('text', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$DiagnosticQuestionOptionsInnerToJson(
  DiagnosticQuestionOptionsInner instance,
) => <String, dynamic>{'id': instance.id, 'text': instance.text};
