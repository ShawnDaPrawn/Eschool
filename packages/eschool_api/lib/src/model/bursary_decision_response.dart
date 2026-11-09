//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/bursary_decision.dart';
import 'package:json_annotation/json_annotation.dart';

part 'bursary_decision_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryDecisionResponse {
  /// Returns a new [BursaryDecisionResponse] instance.
  BursaryDecisionResponse({
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
  final BursaryDecision data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: BursaryDecisionResponseCodeEnum.unknownDefaultOpenApi,
  )
  final BursaryDecisionResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryDecisionResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory BursaryDecisionResponse.fromJson(Map<String, dynamic> json) =>
      _$BursaryDecisionResponseFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryDecisionResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum BursaryDecisionResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const BursaryDecisionResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
