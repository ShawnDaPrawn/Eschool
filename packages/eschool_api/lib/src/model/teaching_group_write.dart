//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_group_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingGroupWrite {
  /// Returns a new [TeachingGroupWrite] instance.
  TeachingGroupWrite({
    required this.name,

    required this.subjectId,

    required this.gradeLevelId,

    required this.sessionYearId,

    this.classSectionId,

    required this.active,

    this.revision,
  });

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
  @JsonKey(name: r'class_section_id', required: false, includeIfNull: false)
  final int? classSectionId;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingGroupWrite &&
          other.name == name &&
          other.subjectId == subjectId &&
          other.gradeLevelId == gradeLevelId &&
          other.sessionYearId == sessionYearId &&
          other.classSectionId == classSectionId &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      name.hashCode +
      subjectId.hashCode +
      gradeLevelId.hashCode +
      sessionYearId.hashCode +
      (classSectionId == null ? 0 : classSectionId.hashCode) +
      active.hashCode +
      revision.hashCode;

  factory TeachingGroupWrite.fromJson(Map<String, dynamic> json) =>
      _$TeachingGroupWriteFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingGroupWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
