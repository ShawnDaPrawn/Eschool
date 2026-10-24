// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_matrix_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterMatrixResponse _$AcademicRegisterMatrixResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicRegisterMatrixResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicRegisterMatrixResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicRegisterMatrixData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicRegisterMatrixResponseToJson(
  AcademicRegisterMatrixResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
