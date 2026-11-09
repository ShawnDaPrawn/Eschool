// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diagnostic_attempt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiagnosticAttemptResponse _$DiagnosticAttemptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DiagnosticAttemptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = DiagnosticAttemptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => DiagnosticAttempt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$DiagnosticAttemptResponseToJson(
  DiagnosticAttemptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
