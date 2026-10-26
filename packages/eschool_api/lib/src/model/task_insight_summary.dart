//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/task_insight_question.dart';
import 'package:json_annotation/json_annotation.dart';

part 'task_insight_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TaskInsightSummary {
  /// Returns a new [TaskInsightSummary] instance.
  TaskInsightSummary({
    required this.groupId,

    required this.taskId,

    required this.canonicalTaskId,

    required this.learnerCount,

    required this.hasQuestionEvidence,

    required this.questions,
  });

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'task_id', required: true, includeIfNull: false)
  final int taskId;

  // minimum: 1
  @JsonKey(name: r'canonical_task_id', required: true, includeIfNull: false)
  final int canonicalTaskId;

  // minimum: 0
  @JsonKey(name: r'learner_count', required: true, includeIfNull: false)
  final int learnerCount;

  @JsonKey(name: r'has_question_evidence', required: true, includeIfNull: false)
  final bool hasQuestionEvidence;

  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final List<TaskInsightQuestion> questions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TaskInsightSummary &&
          other.groupId == groupId &&
          other.taskId == taskId &&
          other.canonicalTaskId == canonicalTaskId &&
          other.learnerCount == learnerCount &&
          other.hasQuestionEvidence == hasQuestionEvidence &&
          other.questions == questions;

  @override
  int get hashCode =>
      groupId.hashCode +
      taskId.hashCode +
      canonicalTaskId.hashCode +
      learnerCount.hashCode +
      hasQuestionEvidence.hashCode +
      questions.hashCode;

  factory TaskInsightSummary.fromJson(Map<String, dynamic> json) =>
      _$TaskInsightSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$TaskInsightSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
