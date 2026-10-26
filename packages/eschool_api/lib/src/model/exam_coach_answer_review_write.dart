//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_answer_review_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachAnswerReviewWrite {
  /// Returns a new [ExamCoachAnswerReviewWrite] instance.
  ExamCoachAnswerReviewWrite({
    required this.operationId,

    required this.expectedRevision,

    required this.markUnits,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  // minimum: 0
  // maximum: 100000
  @JsonKey(name: r'mark_units', required: true, includeIfNull: false)
  final int markUnits;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachAnswerReviewWrite &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.markUnits == markUnits &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      markUnits.hashCode +
      reason.hashCode;

  factory ExamCoachAnswerReviewWrite.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachAnswerReviewWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachAnswerReviewWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
