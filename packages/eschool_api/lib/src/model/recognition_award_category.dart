//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_award_category.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionAwardCategory {
  /// Returns a new [RecognitionAwardCategory] instance.
  RecognitionAwardCategory({
    required this.id,

    required this.name,

    required this.parentId,

    required this.scopeKind,

    required this.gradeLevelId,

    required this.subjectId,

    required this.activityGroupId,

    required this.active,

    required this.revision,

    required this.canManage,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'parent_id', required: true, includeIfNull: true)
  final int? parentId;

  @JsonKey(name: r'scope_kind', required: true, includeIfNull: false)
  final String scopeKind;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: true)
  final int? gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: true)
  final int? subjectId;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: true)
  final int? activityGroupId;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionAwardCategory &&
          other.id == id &&
          other.name == name &&
          other.parentId == parentId &&
          other.scopeKind == scopeKind &&
          other.gradeLevelId == gradeLevelId &&
          other.subjectId == subjectId &&
          other.activityGroupId == activityGroupId &&
          other.active == active &&
          other.revision == revision &&
          other.canManage == canManage;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (parentId == null ? 0 : parentId.hashCode) +
      scopeKind.hashCode +
      (gradeLevelId == null ? 0 : gradeLevelId.hashCode) +
      (subjectId == null ? 0 : subjectId.hashCode) +
      (activityGroupId == null ? 0 : activityGroupId.hashCode) +
      active.hashCode +
      revision.hashCode +
      canManage.hashCode;

  factory RecognitionAwardCategory.fromJson(Map<String, dynamic> json) =>
      _$RecognitionAwardCategoryFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionAwardCategoryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
