//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/weekly_digest_child.dart';
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_access.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestAccess {
  /// Returns a new [WeeklyDigestAccess] instance.
  WeeklyDigestAccess({
    required this.schoolId,

    required this.accountId,

    required this.parent,

    required this.canManage,

    required this.children,

    required this.language,

    required this.preferenceRevision,

    required this.languages,

    required this.approvedLanguages,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'account_id', required: true, includeIfNull: false)
  final int accountId;

  @JsonKey(name: r'parent', required: true, includeIfNull: false)
  final bool parent;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'children', required: true, includeIfNull: false)
  final List<WeeklyDigestChild> children;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  // minimum: 0
  @JsonKey(name: r'preference_revision', required: true, includeIfNull: false)
  final int preferenceRevision;

  @JsonKey(name: r'languages', required: true, includeIfNull: false)
  final List<String> languages;

  @JsonKey(name: r'approved_languages', required: true, includeIfNull: false)
  final List<String> approvedLanguages;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestAccess &&
          other.schoolId == schoolId &&
          other.accountId == accountId &&
          other.parent == parent &&
          other.canManage == canManage &&
          other.children == children &&
          other.language == language &&
          other.preferenceRevision == preferenceRevision &&
          other.languages == languages &&
          other.approvedLanguages == approvedLanguages;

  @override
  int get hashCode =>
      schoolId.hashCode +
      accountId.hashCode +
      parent.hashCode +
      canManage.hashCode +
      children.hashCode +
      language.hashCode +
      preferenceRevision.hashCode +
      languages.hashCode +
      approvedLanguages.hashCode;

  factory WeeklyDigestAccess.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestAccessFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestAccessToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
