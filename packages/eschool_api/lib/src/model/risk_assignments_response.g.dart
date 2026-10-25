// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_assignments_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskAssignmentsResponse _$RiskAssignmentsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RiskAssignmentsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RiskAssignmentsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RiskAssignments.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RiskAssignmentsResponseToJson(
  RiskAssignmentsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
