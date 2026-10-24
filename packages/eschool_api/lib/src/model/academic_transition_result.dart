//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionResult {
  /// Returns a new [AcademicTransitionResult] instance.
  AcademicTransitionResult({
    this.enrolmentId,

    this.membershipIds,

    this.feeProrataRequested,

    this.feeProrataFromOn,

    this.billingPosted,

    this.markRule,

    this.endedEnrolmentIds,

    this.cancelledEnrolmentIds,

    this.endedMembershipIds,

    this.cancelledMembershipIds,

    this.recordsDeleted,

    this.sourceMembershipId,

    this.targetMembershipId,

    this.canonicalMarksReused,

    this.marksCopied,

    this.lastAttendedOn,
  });

  // minimum: 1
  @JsonKey(name: r'enrolment_id', required: false, includeIfNull: false)
  final int? enrolmentId;

  @JsonKey(name: r'membership_ids', required: false, includeIfNull: false)
  final List<int>? membershipIds;

  @JsonKey(
    name: r'fee_prorata_requested',
    required: false,
    includeIfNull: false,
  )
  final bool? feeProrataRequested;

  @JsonKey(name: r'fee_prorata_from_on', required: false, includeIfNull: false)
  final String? feeProrataFromOn;

  @JsonKey(name: r'billing_posted', required: false, includeIfNull: false)
  final bool? billingPosted;

  @JsonKey(name: r'mark_rule', required: false, includeIfNull: false)
  final String? markRule;

  @JsonKey(name: r'ended_enrolment_ids', required: false, includeIfNull: false)
  final List<int>? endedEnrolmentIds;

  @JsonKey(
    name: r'cancelled_enrolment_ids',
    required: false,
    includeIfNull: false,
  )
  final List<int>? cancelledEnrolmentIds;

  @JsonKey(name: r'ended_membership_ids', required: false, includeIfNull: false)
  final List<int>? endedMembershipIds;

  @JsonKey(
    name: r'cancelled_membership_ids',
    required: false,
    includeIfNull: false,
  )
  final List<int>? cancelledMembershipIds;

  @JsonKey(name: r'records_deleted', required: false, includeIfNull: false)
  final bool? recordsDeleted;

  // minimum: 1
  @JsonKey(name: r'source_membership_id', required: false, includeIfNull: false)
  final int? sourceMembershipId;

  // minimum: 1
  @JsonKey(name: r'target_membership_id', required: false, includeIfNull: false)
  final int? targetMembershipId;

  @JsonKey(
    name: r'canonical_marks_reused',
    required: false,
    includeIfNull: false,
  )
  final bool? canonicalMarksReused;

  @JsonKey(name: r'marks_copied', required: false, includeIfNull: false)
  final bool? marksCopied;

  @JsonKey(name: r'last_attended_on', required: false, includeIfNull: false)
  final String? lastAttendedOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionResult &&
          other.enrolmentId == enrolmentId &&
          other.membershipIds == membershipIds &&
          other.feeProrataRequested == feeProrataRequested &&
          other.feeProrataFromOn == feeProrataFromOn &&
          other.billingPosted == billingPosted &&
          other.markRule == markRule &&
          other.endedEnrolmentIds == endedEnrolmentIds &&
          other.cancelledEnrolmentIds == cancelledEnrolmentIds &&
          other.endedMembershipIds == endedMembershipIds &&
          other.cancelledMembershipIds == cancelledMembershipIds &&
          other.recordsDeleted == recordsDeleted &&
          other.sourceMembershipId == sourceMembershipId &&
          other.targetMembershipId == targetMembershipId &&
          other.canonicalMarksReused == canonicalMarksReused &&
          other.marksCopied == marksCopied &&
          other.lastAttendedOn == lastAttendedOn;

  @override
  int get hashCode =>
      enrolmentId.hashCode +
      membershipIds.hashCode +
      feeProrataRequested.hashCode +
      (feeProrataFromOn == null ? 0 : feeProrataFromOn.hashCode) +
      billingPosted.hashCode +
      markRule.hashCode +
      endedEnrolmentIds.hashCode +
      cancelledEnrolmentIds.hashCode +
      endedMembershipIds.hashCode +
      cancelledMembershipIds.hashCode +
      recordsDeleted.hashCode +
      sourceMembershipId.hashCode +
      targetMembershipId.hashCode +
      canonicalMarksReused.hashCode +
      marksCopied.hashCode +
      lastAttendedOn.hashCode;

  factory AcademicTransitionResult.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionResultFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
