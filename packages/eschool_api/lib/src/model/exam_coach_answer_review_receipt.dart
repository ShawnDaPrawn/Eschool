//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_answer_review_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachAnswerReviewReceipt {
  /// Returns a new [ExamCoachAnswerReviewReceipt] instance.
  ExamCoachAnswerReviewReceipt({
    required this.id,

    required this.answerId,

    required this.revision,

    required this.markUnits,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'answer_id', required: true, includeIfNull: false)
  final String answerId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 0
  // maximum: 100000
  @JsonKey(name: r'mark_units', required: true, includeIfNull: false)
  final int markUnits;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachAnswerReviewReceipt &&
          other.id == id &&
          other.answerId == answerId &&
          other.revision == revision &&
          other.markUnits == markUnits;

  @override
  int get hashCode =>
      id.hashCode + answerId.hashCode + revision.hashCode + markUnits.hashCode;

  factory ExamCoachAnswerReviewReceipt.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachAnswerReviewReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachAnswerReviewReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
