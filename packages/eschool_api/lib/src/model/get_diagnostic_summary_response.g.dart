// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_diagnostic_summary_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetDiagnosticSummaryResponse _$GetDiagnosticSummaryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetDiagnosticSummaryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetDiagnosticSummaryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => DiagnosticSummary.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetDiagnosticSummaryResponseToJson(
  GetDiagnosticSummaryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
