//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_policy_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionPolicyWrite {
  /// Returns a new [AcademicTransitionPolicyWrite] instance.
  AcademicTransitionPolicyWrite({
    required this.joinMarkRule,

    required this.changeMarkRule,

    required this.revision,
  });

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
      other is AcademicTransitionPolicyWrite &&
          other.joinMarkRule == joinMarkRule &&
          other.changeMarkRule == changeMarkRule &&
          other.revision == revision;

  @override
  int get hashCode =>
      joinMarkRule.hashCode + changeMarkRule.hashCode + revision.hashCode;

  factory AcademicTransitionPolicyWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionPolicyWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionPolicyWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
