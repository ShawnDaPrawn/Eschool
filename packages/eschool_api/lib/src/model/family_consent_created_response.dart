//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_consent_created.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_created_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentCreatedResponse {
  /// Returns a new [FamilyConsentCreatedResponse] instance.
  FamilyConsentCreatedResponse({
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
  final FamilyConsentCreated data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        FamilyConsentCreatedResponseCodeEnum.unknownDefaultOpenApi,
  )
  final FamilyConsentCreatedResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentCreatedResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory FamilyConsentCreatedResponse.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentCreatedResponseFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentCreatedResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FamilyConsentCreatedResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const FamilyConsentCreatedResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
