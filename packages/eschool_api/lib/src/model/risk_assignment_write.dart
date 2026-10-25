//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'risk_assignment_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskAssignmentWrite {
  /// Returns a new [RiskAssignmentWrite] instance.
  RiskAssignmentWrite({
    required this.operationId,

    required this.sessionYearId,

    required this.expectedRevision,

    required this.startsOn,

    required this.endsOn,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskAssignmentWrite &&
          other.operationId == operationId &&
          other.sessionYearId == sessionYearId &&
          other.expectedRevision == expectedRevision &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn;

  @override
  int get hashCode =>
      operationId.hashCode +
      sessionYearId.hashCode +
      expectedRevision.hashCode +
      startsOn.hashCode +
      endsOn.hashCode;

  factory RiskAssignmentWrite.fromJson(Map<String, dynamic> json) =>
      _$RiskAssignmentWriteFromJson(json);

  Map<String, dynamic> toJson() => _$RiskAssignmentWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
