//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/secure_card_revocation.dart';
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_revocation_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardRevocationResponse {
  /// Returns a new [SecureCardRevocationResponse] instance.
  SecureCardRevocationResponse({
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
  final SecureCardRevocation data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SecureCardRevocationResponseCodeEnum.unknownDefaultOpenApi,
  )
  final SecureCardRevocationResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardRevocationResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SecureCardRevocationResponse.fromJson(Map<String, dynamic> json) =>
      _$SecureCardRevocationResponseFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardRevocationResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SecureCardRevocationResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const SecureCardRevocationResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
