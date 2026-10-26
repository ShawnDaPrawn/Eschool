//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_consent_cancelled.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_cancelled_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentCancelledResponse {
  /// Returns a new [FamilyConsentCancelledResponse] instance.
  FamilyConsentCancelledResponse({
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
  final FamilyConsentCancelled data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        FamilyConsentCancelledResponseCodeEnum.unknownDefaultOpenApi,
  )
  final FamilyConsentCancelledResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentCancelledResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory FamilyConsentCancelledResponse.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentCancelledResponseFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentCancelledResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FamilyConsentCancelledResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const FamilyConsentCancelledResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
