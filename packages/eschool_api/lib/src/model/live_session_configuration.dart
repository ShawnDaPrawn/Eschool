//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'live_session_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LiveSessionConfiguration {
  /// Returns a new [LiveSessionConfiguration] instance.
  LiveSessionConfiguration({
    required this.leaseId,

    required this.channel,

    required this.key,

    required this.host,

    required this.port,

    required this.scheme,

    required this.heartbeatSeconds,

    required this.expiresIn,
  });

  @JsonKey(name: r'lease_id', required: true, includeIfNull: false)
  final String leaseId;

  @JsonKey(name: r'channel', required: true, includeIfNull: false)
  final String channel;

  @JsonKey(name: r'key', required: true, includeIfNull: false)
  final String key;

  @JsonKey(name: r'host', required: true, includeIfNull: false)
  final String host;

  // minimum: 1
  // maximum: 65535
  @JsonKey(name: r'port', required: true, includeIfNull: false)
  final int port;

  @JsonKey(
    name: r'scheme',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LiveSessionConfigurationSchemeEnum.unknownDefaultOpenApi,
  )
  final LiveSessionConfigurationSchemeEnum scheme;

  // minimum: 10
  // maximum: 30
  @JsonKey(name: r'heartbeat_seconds', required: true, includeIfNull: false)
  final int heartbeatSeconds;

  // minimum: 0
  // maximum: 60
  @JsonKey(name: r'expires_in', required: true, includeIfNull: false)
  final int expiresIn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LiveSessionConfiguration &&
          other.leaseId == leaseId &&
          other.channel == channel &&
          other.key == key &&
          other.host == host &&
          other.port == port &&
          other.scheme == scheme &&
          other.heartbeatSeconds == heartbeatSeconds &&
          other.expiresIn == expiresIn;

  @override
  int get hashCode =>
      leaseId.hashCode +
      channel.hashCode +
      key.hashCode +
      host.hashCode +
      port.hashCode +
      scheme.hashCode +
      heartbeatSeconds.hashCode +
      expiresIn.hashCode;

  factory LiveSessionConfiguration.fromJson(Map<String, dynamic> json) =>
      _$LiveSessionConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$LiveSessionConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LiveSessionConfigurationSchemeEnum {
  @JsonValue(r'ws')
  ws(r'ws'),
  @JsonValue(r'wss')
  wss(r'wss'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LiveSessionConfigurationSchemeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
