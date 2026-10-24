// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_assignments_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicAssignmentsResponse _$AcademicAssignmentsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicAssignmentsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicAssignmentsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) =>
          AcademicAssignmentsResponseData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicAssignmentsResponseToJson(
  AcademicAssignmentsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
