// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentConfigurationResponse _$ContentConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ContentConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ContentConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ContentConfiguration.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ContentConfigurationResponseToJson(
  ContentConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
