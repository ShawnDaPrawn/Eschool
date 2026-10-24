//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_grade_policy.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionGradePolicy {
  /// Returns a new [AcademicTransitionGradePolicy] instance.
  AcademicTransitionGradePolicy({
    required this.gradeLevelId,

    required this.joinMarkRule,

    required this.changeMarkRule,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  @JsonKey(name: r'join_mark_rule', required: true, includeIfNull: false)
  final String joinMarkRule;

  @JsonKey(name: r'change_mark_rule', required: true, includeIfNull: false)
  final String changeMarkRule;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionGradePolicy &&
          other.gradeLevelId == gradeLevelId &&
          other.joinMarkRule == joinMarkRule &&
          other.changeMarkRule == changeMarkRule &&
          other.revision == revision;

  @override
  int get hashCode =>
      gradeLevelId.hashCode +
      joinMarkRule.hashCode +
      changeMarkRule.hashCode +
      revision.hashCode;

  factory AcademicTransitionGradePolicy.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionGradePolicyFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionGradePolicyToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
