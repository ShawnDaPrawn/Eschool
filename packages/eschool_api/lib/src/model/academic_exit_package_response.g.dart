// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_exit_package_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicExitPackageResponse _$AcademicExitPackageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicExitPackageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicExitPackageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicExitPackage.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicExitPackageResponseToJson(
  AcademicExitPackageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
