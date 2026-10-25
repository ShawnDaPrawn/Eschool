//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'progress_group.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProgressGroup {
  /// Returns a new [ProgressGroup] instance.
  ProgressGroup({
    required this.id,

    required this.name,

    required this.subjectId,

    required this.subjectName,

    required this.gradeName,

    required this.sessionYearId,
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

  @JsonKey(name: r'grade_name', required: true, includeIfNull: false)
  final String gradeName;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProgressGroup &&
          other.id == id &&
          other.name == name &&
          other.subjectId == subjectId &&
          other.subjectName == subjectName &&
          other.gradeName == gradeName &&
          other.sessionYearId == sessionYearId;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      subjectId.hashCode +
      subjectName.hashCode +
      gradeName.hashCode +
      sessionYearId.hashCode;

  factory ProgressGroup.fromJson(Map<String, dynamic> json) =>
      _$ProgressGroupFromJson(json);

  Map<String, dynamic> toJson() => _$ProgressGroupToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
