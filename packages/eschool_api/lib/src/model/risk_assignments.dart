//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/risk_assignment.dart';
import 'package:json_annotation/json_annotation.dart';

part 'risk_assignments.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskAssignments {
  /// Returns a new [RiskAssignments] instance.
  RiskAssignments({
    required this.learnerId,

    required this.sessionYearId,

    required this.assignments,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'assignments', required: true, includeIfNull: false)
  final List<RiskAssignment> assignments;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskAssignments &&
          other.learnerId == learnerId &&
          other.sessionYearId == sessionYearId &&
          other.assignments == assignments;

  @override
  int get hashCode =>
      learnerId.hashCode + sessionYearId.hashCode + assignments.hashCode;

  factory RiskAssignments.fromJson(Map<String, dynamic> json) =>
      _$RiskAssignmentsFromJson(json);

  Map<String, dynamic> toJson() => _$RiskAssignmentsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
