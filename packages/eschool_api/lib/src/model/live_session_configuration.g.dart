// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'live_session_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LiveSessionConfiguration _$LiveSessionConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LiveSessionConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'lease_id',
        'channel',
        'key',
        'host',
        'port',
        'scheme',
        'heartbeat_seconds',
        'expires_in',
      ],
    );
    final val = LiveSessionConfiguration(
      leaseId: $checkedConvert('lease_id', (v) => v as String),
      channel: $checkedConvert('channel', (v) => v as String),
      key: $checkedConvert('key', (v) => v as String),
      host: $checkedConvert('host', (v) => v as String),
      port: $checkedConvert('port', (v) => (v as num).toInt()),
      scheme: $checkedConvert(
        'scheme',
        (v) => $enumDecode(
          _$LiveSessionConfigurationSchemeEnumEnumMap,
          v,
          unknownValue:
              LiveSessionConfigurationSchemeEnum.unknownDefaultOpenApi,
        ),
      ),
      heartbeatSeconds: $checkedConvert(
        'heartbeat_seconds',
        (v) => (v as num).toInt(),
      ),
      expiresIn: $checkedConvert('expires_in', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'leaseId': 'lease_id',
    'heartbeatSeconds': 'heartbeat_seconds',
    'expiresIn': 'expires_in',
  },
);

Map<String, dynamic> _$LiveSessionConfigurationToJson(
  LiveSessionConfiguration instance,
) => <String, dynamic>{
  'lease_id': instance.leaseId,
  'channel': instance.channel,
  'key': instance.key,
  'host': instance.host,
  'port': instance.port,
  'scheme': _$LiveSessionConfigurationSchemeEnumEnumMap[instance.scheme]!,
  'heartbeat_seconds': instance.heartbeatSeconds,
  'expires_in': instance.expiresIn,
};

const _$LiveSessionConfigurationSchemeEnumEnumMap = {
  LiveSessionConfigurationSchemeEnum.ws: 'ws',
  LiveSessionConfigurationSchemeEnum.wss: 'wss',
  LiveSessionConfigurationSchemeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
