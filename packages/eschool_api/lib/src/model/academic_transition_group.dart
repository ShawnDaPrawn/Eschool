//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_group.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionGroup {
  /// Returns a new [AcademicTransitionGroup] instance.
  AcademicTransitionGroup({
    required this.id,

    required this.name,

    required this.subjectName,

    required this.gradeLevelId,

    required this.classSectionId,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: true, includeIfNull: true)
  final int? classSectionId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionGroup &&
          other.id == id &&
          other.name == name &&
          other.subjectName == subjectName &&
          other.gradeLevelId == gradeLevelId &&
          other.classSectionId == classSectionId;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      subjectName.hashCode +
      gradeLevelId.hashCode +
      (classSectionId == null ? 0 : classSectionId.hashCode);

  factory AcademicTransitionGroup.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionGroupFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionGroupToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
