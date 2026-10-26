// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_foundation_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetFoundationConfigurationResponse _$GetFoundationConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetFoundationConfigurationResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetFoundationConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FoundationConfiguration.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetFoundationConfigurationResponseToJson(
  GetFoundationConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
