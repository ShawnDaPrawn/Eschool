//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/risk_learner_choices.dart';
import 'package:json_annotation/json_annotation.dart';

part 'risk_learner_choices_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskLearnerChoicesResponse {
  /// Returns a new [RiskLearnerChoicesResponse] instance.
  RiskLearnerChoicesResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  // minimum: 0
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final RiskLearnerChoices data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskLearnerChoicesResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory RiskLearnerChoicesResponse.fromJson(Map<String, dynamic> json) =>
      _$RiskLearnerChoicesResponseFromJson(json);

  Map<String, dynamic> toJson() => _$RiskLearnerChoicesResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
