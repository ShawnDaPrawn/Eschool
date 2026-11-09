//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'competition_decision.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CompetitionDecision {
  /// Returns a new [CompetitionDecision] instance.
  CompetitionDecision({required this.operationId, required this.decision});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'decision',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CompetitionDecisionDecisionEnum.unknownDefaultOpenApi,
  )
  final CompetitionDecisionDecisionEnum decision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CompetitionDecision &&
          other.operationId == operationId &&
          other.decision == decision;

  @override
  int get hashCode => operationId.hashCode + decision.hashCode;

  factory CompetitionDecision.fromJson(Map<String, dynamic> json) =>
      _$CompetitionDecisionFromJson(json);

  Map<String, dynamic> toJson() => _$CompetitionDecisionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CompetitionDecisionDecisionEnum {
  @JsonValue(r'accept')
  accept(r'accept'),
  @JsonValue(r'decline')
  decline(r'decline'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CompetitionDecisionDecisionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
