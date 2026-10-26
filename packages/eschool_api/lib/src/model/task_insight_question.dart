//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'task_insight_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TaskInsightQuestion {
  /// Returns a new [TaskInsightQuestion] instance.
  TaskInsightQuestion({
    required this.questionId,

    required this.number,

    required this.topicKeys,

    required this.maxMark,

    required this.evidenceCount,

    required this.unknownCount,

    required this.missedCount,

    required this.averageMark,
  });

  // minimum: 1
  @JsonKey(name: r'question_id', required: true, includeIfNull: false)
  final int questionId;

  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final String number;

  @JsonKey(name: r'topic_keys', required: true, includeIfNull: false)
  final List<String> topicKeys;

  @JsonKey(name: r'max_mark', required: true, includeIfNull: false)
  final String maxMark;

  // minimum: 0
  @JsonKey(name: r'evidence_count', required: true, includeIfNull: false)
  final int evidenceCount;

  // minimum: 0
  @JsonKey(name: r'unknown_count', required: true, includeIfNull: false)
  final int unknownCount;

  // minimum: 0
  @JsonKey(name: r'missed_count', required: true, includeIfNull: false)
  final int missedCount;

  @JsonKey(name: r'average_mark', required: true, includeIfNull: true)
  final String? averageMark;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TaskInsightQuestion &&
          other.questionId == questionId &&
          other.number == number &&
          other.topicKeys == topicKeys &&
          other.maxMark == maxMark &&
          other.evidenceCount == evidenceCount &&
          other.unknownCount == unknownCount &&
          other.missedCount == missedCount &&
          other.averageMark == averageMark;

  @override
  int get hashCode =>
      questionId.hashCode +
      number.hashCode +
      topicKeys.hashCode +
      maxMark.hashCode +
      evidenceCount.hashCode +
      unknownCount.hashCode +
      missedCount.hashCode +
      (averageMark == null ? 0 : averageMark.hashCode);

  factory TaskInsightQuestion.fromJson(Map<String, dynamic> json) =>
      _$TaskInsightQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$TaskInsightQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
