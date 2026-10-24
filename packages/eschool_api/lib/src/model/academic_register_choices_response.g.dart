// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_choices_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterChoicesResponse _$AcademicRegisterChoicesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicRegisterChoicesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicRegisterChoicesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicRegisterChoicesData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicRegisterChoicesResponseToJson(
  AcademicRegisterChoicesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
