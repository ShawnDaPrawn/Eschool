//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_transition_grade_policy.dart';
import 'package:eschool_api/src/model/academic_transition_input.dart';
import 'package:eschool_api/src/model/academic_transition_state.dart';
import 'package:eschool_api/src/model/academic_transition_assignment.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_preview.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionPreview {
  /// Returns a new [AcademicTransitionPreview] instance.
  AcademicTransitionPreview({
    required this.input,

    required this.fingerprint,

    required this.learnerName,

    required this.gradeLevelId,

    required this.homeroomName,

    required this.policy,

    required this.markRule,

    required this.membershipEndsOn,

    required this.groupAssignments,

    required this.state,
  });

  @JsonKey(name: r'input', required: true, includeIfNull: false)
  final AcademicTransitionInput input;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: true)
  final int? gradeLevelId;

  @JsonKey(name: r'homeroom_name', required: true, includeIfNull: false)
  final String homeroomName;

  @JsonKey(name: r'policy', required: true, includeIfNull: true)
  final AcademicTransitionGradePolicy? policy;

  @JsonKey(name: r'mark_rule', required: true, includeIfNull: true)
  final String? markRule;

  @JsonKey(name: r'membership_ends_on', required: true, includeIfNull: false)
  final String membershipEndsOn;

  @JsonKey(name: r'group_assignments', required: true, includeIfNull: false)
  final List<AcademicTransitionAssignment> groupAssignments;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final AcademicTransitionState state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionPreview &&
          other.input == input &&
          other.fingerprint == fingerprint &&
          other.learnerName == learnerName &&
          other.gradeLevelId == gradeLevelId &&
          other.homeroomName == homeroomName &&
          other.policy == policy &&
          other.markRule == markRule &&
          other.membershipEndsOn == membershipEndsOn &&
          other.groupAssignments == groupAssignments &&
          other.state == state;

  @override
  int get hashCode =>
      input.hashCode +
      fingerprint.hashCode +
      learnerName.hashCode +
      (gradeLevelId == null ? 0 : gradeLevelId.hashCode) +
      homeroomName.hashCode +
      (policy == null ? 0 : policy.hashCode) +
      (markRule == null ? 0 : markRule.hashCode) +
      membershipEndsOn.hashCode +
      groupAssignments.hashCode +
      state.hashCode;

  factory AcademicTransitionPreview.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionPreviewFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionPreviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
