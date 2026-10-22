//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/context_user.dart';
import 'package:eschool_api/src/model/module.dart';
import 'package:eschool_api/src/model/notification_rule.dart';
import 'package:eschool_api/src/model/school_membership.dart';
import 'package:eschool_api/src/model/child.dart';
import 'package:eschool_api/src/model/current_school.dart';
import 'package:json_annotation/json_annotation.dart';

part 'context.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class Context {
  /// Returns a new [Context] instance.
  Context({
    required this.user,

    required this.schools,

    required this.currentSchool,

    required this.modules,

    required this.notificationRules,

    required this.featureFlags,

    required this.signInMethods,

    required this.children,
  });

  @JsonKey(name: r'user', required: true, includeIfNull: false)
  final ContextUser user;

  /// Every school the user belongs to, with their roles there (one school until Phase 1).
  @JsonKey(name: r'schools', required: true, includeIfNull: false)
  final List<SchoolMembership> schools;

  @JsonKey(name: r'current_school', required: true, includeIfNull: false)
  final CurrentSchool currentSchool;

  /// Modules switched on at the current school (empty until Phase 1).
  @JsonKey(name: r'modules', required: true, includeIfNull: false)
  final List<Module> modules;

  /// The current school's notification rules for this profile (empty until Phase 1).
  @JsonKey(name: r'notification_rules', required: true, includeIfNull: false)
  final List<NotificationRule> notificationRules;

  /// Feature switches by key (empty until Phase 1).
  @JsonKey(name: r'feature_flags', required: true, includeIfNull: false)
  final Map<String, bool> featureFlags;

  /// How this user may sign in at the current school, for example `password` (passkeys, Google, Microsoft and email links arrive in Phase 1).
  @JsonKey(name: r'sign_in_methods', required: true, includeIfNull: false)
  final List<String> signInMethods;

  /// A parent's children (as father, mother or guardian); empty for everyone else.
  @JsonKey(name: r'children', required: true, includeIfNull: false)
  final List<Child> children;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is Context &&
          other.user == user &&
          other.schools == schools &&
          other.currentSchool == currentSchool &&
          other.modules == modules &&
          other.notificationRules == notificationRules &&
          other.featureFlags == featureFlags &&
          other.signInMethods == signInMethods &&
          other.children == children;

  @override
  int get hashCode =>
      user.hashCode +
      schools.hashCode +
      currentSchool.hashCode +
      modules.hashCode +
      notificationRules.hashCode +
      featureFlags.hashCode +
      signInMethods.hashCode +
      children.hashCode;

  factory Context.fromJson(Map<String, dynamic> json) =>
      _$ContextFromJson(json);

  Map<String, dynamic> toJson() => _$ContextToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
