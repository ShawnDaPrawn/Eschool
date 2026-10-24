//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_effective_rules.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationEffectiveRules {
  /// Returns a new [CommunicationEffectiveRules] instance.
  CommunicationEffectiveRules({
    required this.allowSchool,

    required this.requiresApproval,

    required this.gradeHeadExempt,

    required this.headGrades,

    required this.versions,
  });

  @JsonKey(name: r'allow_school', required: true, includeIfNull: false)
  final bool allowSchool;

  @JsonKey(name: r'requires_approval', required: true, includeIfNull: false)
  final bool requiresApproval;

  @JsonKey(name: r'grade_head_exempt', required: true, includeIfNull: false)
  final bool gradeHeadExempt;

  @JsonKey(name: r'head_grades', required: true, includeIfNull: false)
  final Set<int> headGrades;

  @JsonKey(name: r'versions', required: true, includeIfNull: false)
  final List<List<int>> versions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationEffectiveRules &&
          other.allowSchool == allowSchool &&
          other.requiresApproval == requiresApproval &&
          other.gradeHeadExempt == gradeHeadExempt &&
          other.headGrades == headGrades &&
          other.versions == versions;

  @override
  int get hashCode =>
      allowSchool.hashCode +
      requiresApproval.hashCode +
      gradeHeadExempt.hashCode +
      headGrades.hashCode +
      versions.hashCode;

  factory CommunicationEffectiveRules.fromJson(Map<String, dynamic> json) =>
      _$CommunicationEffectiveRulesFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationEffectiveRulesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
