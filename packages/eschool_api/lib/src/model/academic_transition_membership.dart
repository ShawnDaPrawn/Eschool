//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_membership.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionMembership {
  /// Returns a new [AcademicTransitionMembership] instance.
  AcademicTransitionMembership({
    required this.id,

    required this.teachingGroupId,

    required this.sessionYearId,

    required this.gradeLevelId,

    required this.revision,

    required this.startsOn,

    required this.endsOn,

    required this.markRule,

    required this.cancelledAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: true, includeIfNull: false)
  final int teachingGroupId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: true)
  final int? sessionYearId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: true)
  final int? gradeLevelId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(name: r'mark_rule', required: true, includeIfNull: false)
  final String markRule;

  @JsonKey(name: r'cancelled_at', required: true, includeIfNull: true)
  final DateTime? cancelledAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionMembership &&
          other.id == id &&
          other.teachingGroupId == teachingGroupId &&
          other.sessionYearId == sessionYearId &&
          other.gradeLevelId == gradeLevelId &&
          other.revision == revision &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.markRule == markRule &&
          other.cancelledAt == cancelledAt;

  @override
  int get hashCode =>
      id.hashCode +
      teachingGroupId.hashCode +
      (sessionYearId == null ? 0 : sessionYearId.hashCode) +
      (gradeLevelId == null ? 0 : gradeLevelId.hashCode) +
      revision.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      markRule.hashCode +
      (cancelledAt == null ? 0 : cancelledAt.hashCode);

  factory AcademicTransitionMembership.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionMembershipFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionMembershipToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
