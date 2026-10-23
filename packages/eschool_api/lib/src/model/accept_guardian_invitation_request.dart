//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'accept_guardian_invitation_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcceptGuardianInvitationRequest {
  /// Returns a new [AcceptGuardianInvitationRequest] instance.
  AcceptGuardianInvitationRequest({this.password, this.deviceType, this.fcmId});

  @JsonKey(name: r'password', required: false, includeIfNull: false)
  final String? password;

  @JsonKey(
    name: r'device_type',
    required: false,
    includeIfNull: false,
    unknownEnumValue:
        AcceptGuardianInvitationRequestDeviceTypeEnum.unknownDefaultOpenApi,
  )
  final AcceptGuardianInvitationRequestDeviceTypeEnum? deviceType;

  @JsonKey(name: r'fcm_id', required: false, includeIfNull: false)
  final String? fcmId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcceptGuardianInvitationRequest &&
          other.password == password &&
          other.deviceType == deviceType &&
          other.fcmId == fcmId;

  @override
  int get hashCode => password.hashCode + deviceType.hashCode + fcmId.hashCode;

  factory AcceptGuardianInvitationRequest.fromJson(Map<String, dynamic> json) =>
      _$AcceptGuardianInvitationRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcceptGuardianInvitationRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcceptGuardianInvitationRequestDeviceTypeEnum {
  @JsonValue(r'android')
  android(r'android'),
  @JsonValue(r'ios')
  ios(r'ios'),
  @JsonValue(r'web')
  web(r'web'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcceptGuardianInvitationRequestDeviceTypeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
