//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_lesson.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathLesson {
  /// Returns a new [LearningPathLesson] instance.
  LearningPathLesson({
    required this.source_,

    required this.contentVersionId,

    required this.libraryItemVersionId,

    required this.title,

    required this.body,
  });

  @JsonKey(
    name: r'source',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearningPathLessonSource_Enum.unknownDefaultOpenApi,
  )
  final LearningPathLessonSource_Enum source_;

  // minimum: 1
  @JsonKey(name: r'content_version_id', required: true, includeIfNull: true)
  final int? contentVersionId;

  // minimum: 1
  @JsonKey(
    name: r'library_item_version_id',
    required: true,
    includeIfNull: true,
  )
  final int? libraryItemVersionId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'body', required: true, includeIfNull: true)
  final String? body;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathLesson &&
          other.source_ == source_ &&
          other.contentVersionId == contentVersionId &&
          other.libraryItemVersionId == libraryItemVersionId &&
          other.title == title &&
          other.body == body;

  @override
  int get hashCode =>
      source_.hashCode +
      (contentVersionId == null ? 0 : contentVersionId.hashCode) +
      (libraryItemVersionId == null ? 0 : libraryItemVersionId.hashCode) +
      title.hashCode +
      (body == null ? 0 : body.hashCode);

  factory LearningPathLesson.fromJson(Map<String, dynamic> json) =>
      _$LearningPathLessonFromJson(json);

  Map<String, dynamic> toJson() => _$LearningPathLessonToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearningPathLessonSource_Enum {
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'library')
  library_(r'library'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearningPathLessonSource_Enum(this.value);

  final String value;

  @override
  String toString() => value;
}
