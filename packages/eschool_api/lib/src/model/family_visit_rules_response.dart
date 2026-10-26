//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_visit_rules.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_visit_rules_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyVisitRulesResponse {
  /// Returns a new [FamilyVisitRulesResponse] instance.
  FamilyVisitRulesResponse({
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
  final FamilyVisitRules data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FamilyVisitRulesResponseCodeEnum.unknownDefaultOpenApi,
  )
  final FamilyVisitRulesResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyVisitRulesResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory FamilyVisitRulesResponse.fromJson(Map<String, dynamic> json) =>
      _$FamilyVisitRulesResponseFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyVisitRulesResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FamilyVisitRulesResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const FamilyVisitRulesResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
