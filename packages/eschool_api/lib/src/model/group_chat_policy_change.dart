//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_policy_change.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatPolicyChange {
  /// Returns a new [GroupChatPolicyChange] instance.
  GroupChatPolicyChange({
    required this.operationId,

    required this.revision,

    required this.staffLearners,

    required this.staffGuardians,

    required this.staffStaff,

    required this.learnerSharedReplies,

    required this.guardianSharedReplies,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'staff_learners', required: true, includeIfNull: false)
  final bool staffLearners;

  @JsonKey(name: r'staff_guardians', required: true, includeIfNull: false)
  final bool staffGuardians;

  @JsonKey(name: r'staff_staff', required: true, includeIfNull: false)
  final bool staffStaff;

  @JsonKey(
    name: r'learner_shared_replies',
    required: true,
    includeIfNull: false,
  )
  final bool learnerSharedReplies;

  @JsonKey(
    name: r'guardian_shared_replies',
    required: true,
    includeIfNull: false,
  )
  final bool guardianSharedReplies;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatPolicyChange &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.staffLearners == staffLearners &&
          other.staffGuardians == staffGuardians &&
          other.staffStaff == staffStaff &&
          other.learnerSharedReplies == learnerSharedReplies &&
          other.guardianSharedReplies == guardianSharedReplies;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      staffLearners.hashCode +
      staffGuardians.hashCode +
      staffStaff.hashCode +
      learnerSharedReplies.hashCode +
      guardianSharedReplies.hashCode;

  factory GroupChatPolicyChange.fromJson(Map<String, dynamic> json) =>
      _$GroupChatPolicyChangeFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatPolicyChangeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
