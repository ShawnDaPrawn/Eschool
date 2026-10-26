// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerConfigurationResponse _$PeerConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PeerConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PeerConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PeerConfiguration.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$PeerConfigurationResponseToJson(
  PeerConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
