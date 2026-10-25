//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/secure_card_credential.dart';
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_credential_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardCredentialResponse {
  /// Returns a new [SecureCardCredentialResponse] instance.
  SecureCardCredentialResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final SecureCardCredential data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SecureCardCredentialResponseCodeEnum.unknownDefaultOpenApi,
  )
  final SecureCardCredentialResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardCredentialResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SecureCardCredentialResponse.fromJson(Map<String, dynamic> json) =>
      _$SecureCardCredentialResponseFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardCredentialResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SecureCardCredentialResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const SecureCardCredentialResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
