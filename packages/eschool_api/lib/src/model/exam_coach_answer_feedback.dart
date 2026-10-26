//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_answer_feedback.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachAnswerFeedback {
  /// Returns a new [ExamCoachAnswerFeedback] instance.
  ExamCoachAnswerFeedback({
    required this.questionToken,

    required this.markUnits,

    required this.confidenceBasisPoints,

    required this.feedback,
  });

  @JsonKey(name: r'question_token', required: true, includeIfNull: false)
  final String questionToken;

  // minimum: 0
  // maximum: 100000
  @JsonKey(name: r'mark_units', required: true, includeIfNull: false)
  final int markUnits;

  // minimum: 0
  // maximum: 10000
  @JsonKey(
    name: r'confidence_basis_points',
    required: true,
    includeIfNull: false,
  )
  final int confidenceBasisPoints;

  @JsonKey(name: r'feedback', required: true, includeIfNull: false)
  final String feedback;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachAnswerFeedback &&
          other.questionToken == questionToken &&
          other.markUnits == markUnits &&
          other.confidenceBasisPoints == confidenceBasisPoints &&
          other.feedback == feedback;

  @override
  int get hashCode =>
      questionToken.hashCode +
      markUnits.hashCode +
      confidenceBasisPoints.hashCode +
      feedback.hashCode;

  factory ExamCoachAnswerFeedback.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachAnswerFeedbackFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachAnswerFeedbackToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
