//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_result_topic.dart';
import 'package:eschool_api/src/model/practice_test_review.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestResult {
  /// Returns a new [PracticeTestResult] instance.
  PracticeTestResult({
    required this.correctCount,

    required this.markedCount,

    required this.questionCount,

    required this.unansweredCount,

    required this.awaitingMarkerCount,

    required this.memoAvailable,

    required this.memoAvailableAt,

    required this.topics,

    required this.review,
  });

  // minimum: 0
  @JsonKey(name: r'correct_count', required: true, includeIfNull: false)
  final int correctCount;

  // minimum: 0
  @JsonKey(name: r'marked_count', required: true, includeIfNull: false)
  final int markedCount;

  // minimum: 1
  @JsonKey(name: r'question_count', required: true, includeIfNull: false)
  final int questionCount;

  // minimum: 0
  @JsonKey(name: r'unanswered_count', required: true, includeIfNull: false)
  final int unansweredCount;

  // minimum: 0
  @JsonKey(name: r'awaiting_marker_count', required: true, includeIfNull: false)
  final int awaitingMarkerCount;

  @JsonKey(name: r'memo_available', required: true, includeIfNull: false)
  final bool memoAvailable;

  @JsonKey(name: r'memo_available_at', required: true, includeIfNull: true)
  final DateTime? memoAvailableAt;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<PracticeTestResultTopic> topics;

  @JsonKey(name: r'review', required: true, includeIfNull: false)
  final PracticeTestReview review;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestResult &&
          other.correctCount == correctCount &&
          other.markedCount == markedCount &&
          other.questionCount == questionCount &&
          other.unansweredCount == unansweredCount &&
          other.awaitingMarkerCount == awaitingMarkerCount &&
          other.memoAvailable == memoAvailable &&
          other.memoAvailableAt == memoAvailableAt &&
          other.topics == topics &&
          other.review == review;

  @override
  int get hashCode =>
      correctCount.hashCode +
      markedCount.hashCode +
      questionCount.hashCode +
      unansweredCount.hashCode +
      awaitingMarkerCount.hashCode +
      memoAvailable.hashCode +
      (memoAvailableAt == null ? 0 : memoAvailableAt.hashCode) +
      topics.hashCode +
      review.hashCode;

  factory PracticeTestResult.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestResultFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
