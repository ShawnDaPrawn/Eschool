//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_summary.dart';
import 'package:eschool_api/src/model/guardian_invitation_preview_learners_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'guardian_invitation_preview.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GuardianInvitationPreview {
  /// Returns a new [GuardianInvitationPreview] instance.
  GuardianInvitationPreview({
    required this.school,

    required this.email,

    required this.learners,

    required this.expiresAt,

    required this.needsPassword,
  });

  @JsonKey(name: r'school', required: true, includeIfNull: false)
  final SchoolSummary school;

  @JsonKey(name: r'email', required: true, includeIfNull: false)
  final String email;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<GuardianInvitationPreviewLearnersInner> learners;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @JsonKey(name: r'needs_password', required: true, includeIfNull: false)
  final bool needsPassword;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GuardianInvitationPreview &&
          other.school == school &&
          other.email == email &&
          other.learners == learners &&
          other.expiresAt == expiresAt &&
          other.needsPassword == needsPassword;

  @override
  int get hashCode =>
      school.hashCode +
      email.hashCode +
      learners.hashCode +
      expiresAt.hashCode +
      needsPassword.hashCode;

  factory GuardianInvitationPreview.fromJson(Map<String, dynamic> json) =>
      _$GuardianInvitationPreviewFromJson(json);

  Map<String, dynamic> toJson() => _$GuardianInvitationPreviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
