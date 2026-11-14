//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'provider_sign_in_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProviderSignInInput {
  /// Returns a new [ProviderSignInInput] instance.
  ProviderSignInInput({
    required this.school,

    required this.app,

    required this.idToken,

    required this.nonce,

    this.fcmId,

    this.deviceType,
  });

  @JsonKey(name: r'school', required: true, includeIfNull: false)
  final String school;

  @JsonKey(
    name: r'app',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ProviderSignInInputAppEnum.unknownDefaultOpenApi,
  )
  final ProviderSignInInputAppEnum app;

  @JsonKey(name: r'id_token', required: true, includeIfNull: false)
  final String idToken;

  @JsonKey(name: r'nonce', required: true, includeIfNull: false)
  final String nonce;

  @JsonKey(name: r'fcm_id', required: false, includeIfNull: false)
  final String? fcmId;

  @JsonKey(name: r'device_type', required: false, includeIfNull: false)
  final String? deviceType;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProviderSignInInput &&
          other.school == school &&
          other.app == app &&
          other.idToken == idToken &&
          other.nonce == nonce &&
          other.fcmId == fcmId &&
          other.deviceType == deviceType;

  @override
  int get hashCode =>
      school.hashCode +
      app.hashCode +
      idToken.hashCode +
      nonce.hashCode +
      (fcmId == null ? 0 : fcmId.hashCode) +
      (deviceType == null ? 0 : deviceType.hashCode);

  factory ProviderSignInInput.fromJson(Map<String, dynamic> json) =>
      _$ProviderSignInInputFromJson(json);

  Map<String, dynamic> toJson() => _$ProviderSignInInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ProviderSignInInputAppEnum {
  @JsonValue(r'teacher')
  teacher(r'teacher'),
  @JsonValue(r'student')
  student(r'student'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ProviderSignInInputAppEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
