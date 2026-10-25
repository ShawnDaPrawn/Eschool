// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportConfigurationResponse _$SupportConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SupportConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = SupportConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => SupportConfiguration.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SupportConfigurationResponseToJson(
  SupportConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
