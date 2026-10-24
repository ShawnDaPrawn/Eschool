// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionConfigurationResponse
_$AcademicTransitionConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTransitionConfigurationResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicTransitionConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) =>
          AcademicTransitionConfiguration.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicTransitionConfigurationResponseToJson(
  AcademicTransitionConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
