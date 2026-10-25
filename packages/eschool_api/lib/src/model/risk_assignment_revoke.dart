//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'risk_assignment_revoke.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskAssignmentRevoke {
  /// Returns a new [RiskAssignmentRevoke] instance.
  RiskAssignmentRevoke({
    required this.operationId,

    required this.sessionYearId,

    required this.expectedRevision,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskAssignmentRevoke &&
          other.operationId == operationId &&
          other.sessionYearId == sessionYearId &&
          other.expectedRevision == expectedRevision;

  @override
  int get hashCode =>
      operationId.hashCode + sessionYearId.hashCode + expectedRevision.hashCode;

  factory RiskAssignmentRevoke.fromJson(Map<String, dynamic> json) =>
      _$RiskAssignmentRevokeFromJson(json);

  Map<String, dynamic> toJson() => _$RiskAssignmentRevokeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
