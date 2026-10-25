// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_assignments_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportAssignmentsResponse _$SupportAssignmentsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SupportAssignmentsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = SupportAssignmentsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => SupportAssignments.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SupportAssignmentsResponseToJson(
  SupportAssignmentsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
