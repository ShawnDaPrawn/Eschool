//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_selection.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentSelection {
  /// Returns a new [ContentSelection] instance.
  ContentSelection({
    required this.subjectId,

    required this.gradeLevelId,

    required this.sessionYearId,

    required this.title,

    required this.summary,

    required this.groupIds,

    required this.topicIds,
  });

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'summary', required: true, includeIfNull: true)
  final String? summary;

  @JsonKey(name: r'group_ids', required: true, includeIfNull: false)
  final Set<int> groupIds;

  @JsonKey(name: r'topic_ids', required: true, includeIfNull: false)
  final Set<int> topicIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentSelection &&
          other.subjectId == subjectId &&
          other.gradeLevelId == gradeLevelId &&
          other.sessionYearId == sessionYearId &&
          other.title == title &&
          other.summary == summary &&
          other.groupIds == groupIds &&
          other.topicIds == topicIds;

  @override
  int get hashCode =>
      subjectId.hashCode +
      gradeLevelId.hashCode +
      sessionYearId.hashCode +
      title.hashCode +
      (summary == null ? 0 : summary.hashCode) +
      groupIds.hashCode +
      topicIds.hashCode;

  factory ContentSelection.fromJson(Map<String, dynamic> json) =>
      _$ContentSelectionFromJson(json);

  Map<String, dynamic> toJson() => _$ContentSelectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
