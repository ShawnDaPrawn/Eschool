//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_answer_feedback_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachAnswerFeedbackWrite {
  /// Returns a new [ExamCoachAnswerFeedbackWrite] instance.
  ExamCoachAnswerFeedbackWrite({
    required this.operationId,

    required this.working,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'working', required: true, includeIfNull: true)
  final String? working;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachAnswerFeedbackWrite &&
          other.operationId == operationId &&
          other.working == working;

  @override
  int get hashCode =>
      operationId.hashCode + (working == null ? 0 : working.hashCode);

  factory ExamCoachAnswerFeedbackWrite.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachAnswerFeedbackWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachAnswerFeedbackWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
