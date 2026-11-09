//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'fixture_decision.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureDecision {
  /// Returns a new [FixtureDecision] instance.
  FixtureDecision({
    required this.operationId,

    required this.decision,

    this.activityGroupId,

    this.teamLabel,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'decision',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FixtureDecisionDecisionEnum.unknownDefaultOpenApi,
  )
  final FixtureDecisionDecisionEnum decision;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: false, includeIfNull: false)
  final int? activityGroupId;

  @JsonKey(name: r'team_label', required: false, includeIfNull: false)
  final String? teamLabel;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureDecision &&
          other.operationId == operationId &&
          other.decision == decision &&
          other.activityGroupId == activityGroupId &&
          other.teamLabel == teamLabel;

  @override
  int get hashCode =>
      operationId.hashCode +
      decision.hashCode +
      (activityGroupId == null ? 0 : activityGroupId.hashCode) +
      (teamLabel == null ? 0 : teamLabel.hashCode);

  factory FixtureDecision.fromJson(Map<String, dynamic> json) =>
      _$FixtureDecisionFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureDecisionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FixtureDecisionDecisionEnum {
  @JsonValue(r'accept')
  accept(r'accept'),
  @JsonValue(r'decline')
  decline(r'decline'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const FixtureDecisionDecisionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
