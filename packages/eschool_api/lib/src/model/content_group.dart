//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_group.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentGroup {
  /// Returns a new [ContentGroup] instance.
  ContentGroup({
    required this.id,

    required this.name,

    required this.subjectId,

    required this.subjectName,

    required this.gradeLevelId,

    required this.gradeName,
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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentGroup &&
          other.id == id &&
          other.name == name &&
          other.subjectId == subjectId &&
          other.subjectName == subjectName &&
          other.gradeLevelId == gradeLevelId &&
          other.gradeName == gradeName;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      subjectId.hashCode +
      subjectName.hashCode +
      gradeLevelId.hashCode +
      gradeName.hashCode;

  factory ContentGroup.fromJson(Map<String, dynamic> json) =>
      _$ContentGroupFromJson(json);

  Map<String, dynamic> toJson() => _$ContentGroupToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
