//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_topic_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentTopicChoice {
  /// Returns a new [ContentTopicChoice] instance.
  ContentTopicChoice({
    required this.id,

    required this.name,

    required this.subjectId,

    required this.gradeLevelId,

    required this.curriculumVersion,
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

  @JsonKey(name: r'curriculum_version', required: true, includeIfNull: false)
  final int curriculumVersion;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentTopicChoice &&
          other.id == id &&
          other.name == name &&
          other.subjectId == subjectId &&
          other.gradeLevelId == gradeLevelId &&
          other.curriculumVersion == curriculumVersion;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      subjectId.hashCode +
      gradeLevelId.hashCode +
      curriculumVersion.hashCode;

  factory ContentTopicChoice.fromJson(Map<String, dynamic> json) =>
      _$ContentTopicChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$ContentTopicChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
