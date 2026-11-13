//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_topic.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryTopic {
  /// Returns a new [SharedLibraryTopic] instance.
  SharedLibraryTopic({
    required this.id,

    required this.name,

    required this.framework,

    required this.subjectCode,

    required this.grade,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'framework', required: true, includeIfNull: false)
  final String framework;

  @JsonKey(name: r'subject_code', required: true, includeIfNull: false)
  final String subjectCode;

  @JsonKey(name: r'grade', required: true, includeIfNull: false)
  final String grade;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryTopic &&
          other.id == id &&
          other.name == name &&
          other.framework == framework &&
          other.subjectCode == subjectCode &&
          other.grade == grade;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      framework.hashCode +
      subjectCode.hashCode +
      grade.hashCode;

  factory SharedLibraryTopic.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryTopicFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryTopicToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
