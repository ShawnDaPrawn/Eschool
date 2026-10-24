// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_assignment_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicAssignmentResponse _$AcademicAssignmentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicAssignmentResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicAssignmentResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicAssignment.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicAssignmentResponseToJson(
  AcademicAssignmentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
