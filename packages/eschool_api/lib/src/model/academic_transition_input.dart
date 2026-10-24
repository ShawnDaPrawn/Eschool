//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionInput {
  /// Returns a new [AcademicTransitionInput] instance.
  AcademicTransitionInput({
    required this.kind,

    required this.learnerId,

    required this.sessionYearId,

    required this.effectiveOn,

    required this.reason,

    this.classSectionId,

    this.groupIds,

    this.feeProrataRequested,

    this.sourceMembershipId,

    this.targetGroupId,

    this.markRuleOverride,
  });

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'effective_on', required: true, includeIfNull: false)
  final String effectiveOn;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: false, includeIfNull: false)
  final int? classSectionId;

  @JsonKey(name: r'group_ids', required: false, includeIfNull: false)
  final Set<int>? groupIds;

  @JsonKey(
    name: r'fee_prorata_requested',
    required: false,
    includeIfNull: false,
  )
  final bool? feeProrataRequested;

  // minimum: 1
  @JsonKey(name: r'source_membership_id', required: false, includeIfNull: false)
  final int? sourceMembershipId;

  // minimum: 1
  @JsonKey(name: r'target_group_id', required: false, includeIfNull: false)
  final int? targetGroupId;

  @JsonKey(name: r'mark_rule_override', required: false, includeIfNull: false)
  final String? markRuleOverride;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionInput &&
          other.kind == kind &&
          other.learnerId == learnerId &&
          other.sessionYearId == sessionYearId &&
          other.effectiveOn == effectiveOn &&
          other.reason == reason &&
          other.classSectionId == classSectionId &&
          other.groupIds == groupIds &&
          other.feeProrataRequested == feeProrataRequested &&
          other.sourceMembershipId == sourceMembershipId &&
          other.targetGroupId == targetGroupId &&
          other.markRuleOverride == markRuleOverride;

  @override
  int get hashCode =>
      kind.hashCode +
      learnerId.hashCode +
      sessionYearId.hashCode +
      effectiveOn.hashCode +
      reason.hashCode +
      classSectionId.hashCode +
      groupIds.hashCode +
      feeProrataRequested.hashCode +
      sourceMembershipId.hashCode +
      targetGroupId.hashCode +
      (markRuleOverride == null ? 0 : markRuleOverride.hashCode);

  factory AcademicTransitionInput.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionInputFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
