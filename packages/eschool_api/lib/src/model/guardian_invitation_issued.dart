//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'guardian_invitation_issued.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GuardianInvitationIssued {
  /// Returns a new [GuardianInvitationIssued] instance.
  GuardianInvitationIssued({required this.id, required this.expiresAt});

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GuardianInvitationIssued &&
          other.id == id &&
          other.expiresAt == expiresAt;

  @override
  int get hashCode => id.hashCode + expiresAt.hashCode;

  factory GuardianInvitationIssued.fromJson(Map<String, dynamic> json) =>
      _$GuardianInvitationIssuedFromJson(json);

  Map<String, dynamic> toJson() => _$GuardianInvitationIssuedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
