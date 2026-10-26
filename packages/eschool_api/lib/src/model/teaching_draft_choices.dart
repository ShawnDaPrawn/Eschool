//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/teaching_evidence_choice.dart';
import 'package:eschool_api/src/model/teaching_task_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'teaching_draft_choices.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingDraftChoices {
  /// Returns a new [TeachingDraftChoices] instance.
  TeachingDraftChoices({
    required this.schoolId,

    required this.readerId,

    required this.groupId,

    required this.sources,

    required this.topics,

    required this.tasks,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'sources', required: true, includeIfNull: false)
  final List<TeachingEvidenceChoice> sources;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<TeachingEvidenceChoice> topics;

  @JsonKey(name: r'tasks', required: true, includeIfNull: false)
  final List<TeachingTaskChoice> tasks;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingDraftChoices &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.groupId == groupId &&
          other.sources == sources &&
          other.topics == topics &&
          other.tasks == tasks;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      groupId.hashCode +
      sources.hashCode +
      topics.hashCode +
      tasks.hashCode;

  factory TeachingDraftChoices.fromJson(Map<String, dynamic> json) =>
      _$TeachingDraftChoicesFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingDraftChoicesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
