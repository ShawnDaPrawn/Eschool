//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'guardian_invitation_preview_learners_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GuardianInvitationPreviewLearnersInner {
  /// Returns a new [GuardianInvitationPreviewLearnersInner] instance.
  GuardianInvitationPreviewLearnersInner({
    required this.id,

    required this.name,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GuardianInvitationPreviewLearnersInner &&
          other.id == id &&
          other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory GuardianInvitationPreviewLearnersInner.fromJson(
    Map<String, dynamic> json,
  ) => _$GuardianInvitationPreviewLearnersInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$GuardianInvitationPreviewLearnersInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
