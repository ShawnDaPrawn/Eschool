//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_contribution_topic.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryContributionTopic {
  /// Returns a new [SharedLibraryContributionTopic] instance.
  SharedLibraryContributionTopic({
    required this.name,

    required this.framework,

    required this.subjectCode,

    required this.grade,
  });

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
      other is SharedLibraryContributionTopic &&
          other.name == name &&
          other.framework == framework &&
          other.subjectCode == subjectCode &&
          other.grade == grade;

  @override
  int get hashCode =>
      name.hashCode +
      framework.hashCode +
      subjectCode.hashCode +
      grade.hashCode;

  factory SharedLibraryContributionTopic.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryContributionTopicFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryContributionTopicToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
