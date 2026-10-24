// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_settings_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterSettingsResponse _$AcademicRegisterSettingsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicRegisterSettingsResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicRegisterSettingsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicRegisterSettingsData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicRegisterSettingsResponseToJson(
  AcademicRegisterSettingsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
