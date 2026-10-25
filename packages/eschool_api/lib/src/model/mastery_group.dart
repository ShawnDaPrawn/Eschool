//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'mastery_group.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryGroup {
  /// Returns a new [MasteryGroup] instance.
  MasteryGroup({
    required this.id,

    required this.name,

    required this.subjectId,

    required this.subjectName,

    required this.gradeLevelId,

    required this.gradeName,

    required this.sessionYearId,

    required this.yearName,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  @JsonKey(name: r'grade_name', required: true, includeIfNull: false)
  final String gradeName;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'year_name', required: true, includeIfNull: false)
  final String yearName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryGroup &&
          other.id == id &&
          other.name == name &&
          other.subjectId == subjectId &&
          other.subjectName == subjectName &&
          other.gradeLevelId == gradeLevelId &&
          other.gradeName == gradeName &&
          other.sessionYearId == sessionYearId &&
          other.yearName == yearName;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      subjectId.hashCode +
      subjectName.hashCode +
      gradeLevelId.hashCode +
      gradeName.hashCode +
      sessionYearId.hashCode +
      yearName.hashCode;

  factory MasteryGroup.fromJson(Map<String, dynamic> json) =>
      _$MasteryGroupFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryGroupToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
