//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'risk_assignment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskAssignment {
  /// Returns a new [RiskAssignment] instance.
  RiskAssignment({
    required this.id,

    required this.startsOn,

    required this.endsOn,

    required this.revoked,

    required this.revision,

    required this.professionalId,
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

  // minimum: 1
  @JsonKey(name: r'professional_id', required: true, includeIfNull: false)
  final int professionalId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskAssignment &&
          other.id == id &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.revoked == revoked &&
          other.revision == revision &&
          other.professionalId == professionalId;

  @override
  int get hashCode =>
      id.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      revoked.hashCode +
      revision.hashCode +
      professionalId.hashCode;

  factory RiskAssignment.fromJson(Map<String, dynamic> json) =>
      _$RiskAssignmentFromJson(json);

  Map<String, dynamic> toJson() => _$RiskAssignmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
