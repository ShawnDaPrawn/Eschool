//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/guardian_invitation_preview_learners_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'guardian_handle_proposal.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GuardianHandleProposal {
  /// Returns a new [GuardianHandleProposal] instance.
  GuardianHandleProposal({
    required this.id,

    required this.handle,

    required this.expiresAt,

    required this.learner,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'handle', required: true, includeIfNull: false)
  final String handle;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @JsonKey(name: r'learner', required: true, includeIfNull: false)
  final GuardianInvitationPreviewLearnersInner learner;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GuardianHandleProposal &&
          other.id == id &&
          other.handle == handle &&
          other.expiresAt == expiresAt &&
          other.learner == learner;

  @override
  int get hashCode =>
      id.hashCode + handle.hashCode + expiresAt.hashCode + learner.hashCode;

  factory GuardianHandleProposal.fromJson(Map<String, dynamic> json) =>
      _$GuardianHandleProposalFromJson(json);

  Map<String, dynamic> toJson() => _$GuardianHandleProposalToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
