//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_gate_pass_page.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_gate_pass_page_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyGatePassPageResponse {
  /// Returns a new [FamilyGatePassPageResponse] instance.
  FamilyGatePassPageResponse({
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
  final FamilyGatePassPage data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FamilyGatePassPageResponseCodeEnum.unknownDefaultOpenApi,
  )
  final FamilyGatePassPageResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyGatePassPageResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory FamilyGatePassPageResponse.fromJson(Map<String, dynamic> json) =>
      _$FamilyGatePassPageResponseFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyGatePassPageResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FamilyGatePassPageResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const FamilyGatePassPageResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
