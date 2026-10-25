//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'risk_assignment_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskAssignmentResult {
  /// Returns a new [RiskAssignmentResult] instance.
  RiskAssignmentResult({
    required this.id,

    required this.startsOn,

    required this.endsOn,

    required this.revoked,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(name: r'revoked', required: true, includeIfNull: false)
  final bool revoked;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskAssignmentResult &&
          other.id == id &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.revoked == revoked &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      revoked.hashCode +
      revision.hashCode;

  factory RiskAssignmentResult.fromJson(Map<String, dynamic> json) =>
      _$RiskAssignmentResultFromJson(json);

  Map<String, dynamic> toJson() => _$RiskAssignmentResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
