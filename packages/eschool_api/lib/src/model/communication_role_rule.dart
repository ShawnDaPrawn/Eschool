//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_role_rule.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationRoleRule {
  /// Returns a new [CommunicationRoleRule] instance.
  CommunicationRoleRule({
    required this.roleId,

    required this.role,

    required this.allowSchool,

    required this.requiresApproval,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'role_id', required: true, includeIfNull: false)
  final int roleId;

  @JsonKey(name: r'role', required: true, includeIfNull: false)
  final String role;

  @JsonKey(name: r'allow_school', required: true, includeIfNull: false)
  final bool allowSchool;

  @JsonKey(name: r'requires_approval', required: true, includeIfNull: false)
  final bool requiresApproval;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationRoleRule &&
          other.roleId == roleId &&
          other.role == role &&
          other.allowSchool == allowSchool &&
          other.requiresApproval == requiresApproval &&
          other.revision == revision;

  @override
  int get hashCode =>
      roleId.hashCode +
      role.hashCode +
      allowSchool.hashCode +
      requiresApproval.hashCode +
      revision.hashCode;

  factory CommunicationRoleRule.fromJson(Map<String, dynamic> json) =>
      _$CommunicationRoleRuleFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationRoleRuleToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
