//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_transition_enrolment.dart';
import 'package:eschool_api/src/model/academic_transition_membership.dart';
import 'package:eschool_api/src/model/academic_transition_fact.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_state.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionState {
  /// Returns a new [AcademicTransitionState] instance.
  AcademicTransitionState({
    required this.enrolments,

    required this.memberships,

    required this.changes,
  });

  @JsonKey(name: r'enrolments', required: true, includeIfNull: false)
  final List<AcademicTransitionEnrolment> enrolments;

  @JsonKey(name: r'memberships', required: true, includeIfNull: false)
  final List<AcademicTransitionMembership> memberships;

  @JsonKey(name: r'changes', required: true, includeIfNull: false)
  final List<AcademicTransitionFact> changes;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionState &&
          other.enrolments == enrolments &&
          other.memberships == memberships &&
          other.changes == changes;

  @override
  int get hashCode =>
      enrolments.hashCode + memberships.hashCode + changes.hashCode;

  factory AcademicTransitionState.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionStateFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionStateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
