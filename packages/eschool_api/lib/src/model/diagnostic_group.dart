//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/diagnostic_group_learners_inner.dart';
import 'package:eschool_api/src/model/diagnostic_group_topics_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'diagnostic_group.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiagnosticGroup {
  /// Returns a new [DiagnosticGroup] instance.
  DiagnosticGroup({
    required this.groupId,

    required this.groupName,

    required this.subjectName,

    required this.available,

    required this.title,

    required this.topics,

    required this.learners,
  });

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'group_name', required: true, includeIfNull: false)
  final String groupName;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  @JsonKey(name: r'title', required: true, includeIfNull: true)
  final String? title;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<DiagnosticGroupTopicsInner> topics;

  /// By name, never ranked; no total per learner.
  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<DiagnosticGroupLearnersInner> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiagnosticGroup &&
          other.groupId == groupId &&
          other.groupName == groupName &&
          other.subjectName == subjectName &&
          other.available == available &&
          other.title == title &&
          other.topics == topics &&
          other.learners == learners;

  @override
  int get hashCode =>
      groupId.hashCode +
      groupName.hashCode +
      subjectName.hashCode +
      available.hashCode +
      (title == null ? 0 : title.hashCode) +
      topics.hashCode +
      learners.hashCode;

  factory DiagnosticGroup.fromJson(Map<String, dynamic> json) =>
      _$DiagnosticGroupFromJson(json);

  Map<String, dynamic> toJson() => _$DiagnosticGroupToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
