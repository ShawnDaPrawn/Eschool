//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'update_my_notification_preferences_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class UpdateMyNotificationPreferencesRequest {
  /// Returns a new [UpdateMyNotificationPreferencesRequest] instance.
  UpdateMyNotificationPreferencesRequest({
    required this.type,

    required this.channel,

    required this.enabled,
  });

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(
    name: r'channel',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        UpdateMyNotificationPreferencesRequestChannelEnum.unknownDefaultOpenApi,
  )
  final UpdateMyNotificationPreferencesRequestChannelEnum channel;

  @JsonKey(name: r'enabled', required: true, includeIfNull: false)
  final bool enabled;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is UpdateMyNotificationPreferencesRequest &&
          other.type == type &&
          other.channel == channel &&
          other.enabled == enabled;

  @override
  int get hashCode => type.hashCode + channel.hashCode + enabled.hashCode;

  factory UpdateMyNotificationPreferencesRequest.fromJson(
    Map<String, dynamic> json,
  ) => _$UpdateMyNotificationPreferencesRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$UpdateMyNotificationPreferencesRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum UpdateMyNotificationPreferencesRequestChannelEnum {
  @JsonValue(r'push')
  push(r'push'),
  @JsonValue(r'email')
  email(r'email'),
  @JsonValue(r'in_app')
  inApp(r'in_app'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const UpdateMyNotificationPreferencesRequestChannelEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
