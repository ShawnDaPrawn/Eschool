//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_consent_signed.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_signed_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentSignedResponse {
  /// Returns a new [FamilyConsentSignedResponse] instance.
  FamilyConsentSignedResponse({
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
  final FamilyConsentSigned data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FamilyConsentSignedResponseCodeEnum.unknownDefaultOpenApi,
  )
  final FamilyConsentSignedResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentSignedResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory FamilyConsentSignedResponse.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentSignedResponseFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentSignedResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FamilyConsentSignedResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const FamilyConsentSignedResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
