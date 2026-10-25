// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_assignment_result_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskAssignmentResultResponse _$RiskAssignmentResultResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RiskAssignmentResultResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RiskAssignmentResultResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RiskAssignmentResult.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RiskAssignmentResultResponseToJson(
  RiskAssignmentResultResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
