//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/class_grade_mapping_grade_level.dart';
import 'package:json_annotation/json_annotation.dart';

part 'class_grade_mapping.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ClassGradeMapping {
  /// Returns a new [ClassGradeMapping] instance.
  ClassGradeMapping({
    required this.classId,

    required this.gradeLevelId,

    required this.gradeLevel,
  });

  @JsonKey(name: r'class_id', required: true, includeIfNull: false)
  final int classId;

  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: true)
  final int? gradeLevelId;

  @JsonKey(name: r'grade_level', required: true, includeIfNull: true)
  final ClassGradeMappingGradeLevel? gradeLevel;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ClassGradeMapping &&
          other.classId == classId &&
          other.gradeLevelId == gradeLevelId &&
          other.gradeLevel == gradeLevel;

  @override
  int get hashCode =>
      classId.hashCode +
      (gradeLevelId == null ? 0 : gradeLevelId.hashCode) +
      (gradeLevel == null ? 0 : gradeLevel.hashCode);

  factory ClassGradeMapping.fromJson(Map<String, dynamic> json) =>
      _$ClassGradeMappingFromJson(json);

  Map<String, dynamic> toJson() => _$ClassGradeMappingToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
