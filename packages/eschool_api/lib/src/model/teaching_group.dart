//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_group.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingGroup {
  /// Returns a new [TeachingGroup] instance.
  TeachingGroup({
    required this.id,

    required this.name,

    required this.subjectId,

    required this.gradeLevelId,

    required this.sessionYearId,

    required this.classSectionId,

    required this.active,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: true, includeIfNull: true)
  final int? classSectionId;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingGroup &&
          other.id == id &&
          other.name == name &&
          other.subjectId == subjectId &&
          other.gradeLevelId == gradeLevelId &&
          other.sessionYearId == sessionYearId &&
          other.classSectionId == classSectionId &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      subjectId.hashCode +
      gradeLevelId.hashCode +
      sessionYearId.hashCode +
      (classSectionId == null ? 0 : classSectionId.hashCode) +
      active.hashCode +
      revision.hashCode;

  factory TeachingGroup.fromJson(Map<String, dynamic> json) =>
      _$TeachingGroupFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingGroupToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
