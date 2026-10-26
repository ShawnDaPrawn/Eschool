//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_answer_review.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachAnswerReview {
  /// Returns a new [ExamCoachAnswerReview] instance.
  ExamCoachAnswerReview({
    required this.id,

    required this.markUnits,

    required this.reason,

    required this.answerRevision,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 0
  // maximum: 100000
  @JsonKey(name: r'mark_units', required: true, includeIfNull: false)
  final int markUnits;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  // minimum: 1
  @JsonKey(name: r'answer_revision', required: true, includeIfNull: false)
  final int answerRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachAnswerReview &&
          other.id == id &&
          other.markUnits == markUnits &&
          other.reason == reason &&
          other.answerRevision == answerRevision;

  @override
  int get hashCode =>
      id.hashCode +
      markUnits.hashCode +
      reason.hashCode +
      answerRevision.hashCode;

  factory ExamCoachAnswerReview.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachAnswerReviewFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachAnswerReviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
