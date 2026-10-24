// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationConfigurationResponse _$CommunicationConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationConfigurationResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CommunicationConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CommunicationConfiguration.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationConfigurationResponseToJson(
  CommunicationConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
