// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressConfigurationResponse _$ProgressConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProgressConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ProgressConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ProgressConfiguration.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ProgressConfigurationResponseToJson(
  ProgressConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
