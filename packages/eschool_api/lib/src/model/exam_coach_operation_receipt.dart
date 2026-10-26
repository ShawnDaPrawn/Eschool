//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_paper_receipt.dart';
import 'package:eschool_api/src/model/exam_coach_answer_review_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_operation_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachOperationReceipt {
  /// Returns a new [ExamCoachOperationReceipt] instance.
  ExamCoachOperationReceipt({
    required this.kind,

    required this.operationId,

    required this.paper,

    required this.review,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ExamCoachOperationReceiptKindEnum.unknownDefaultOpenApi,
  )
  final ExamCoachOperationReceiptKindEnum kind;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'paper', required: true, includeIfNull: true)
  final ExamCoachPaperReceipt? paper;

  @JsonKey(name: r'review', required: true, includeIfNull: true)
  final ExamCoachAnswerReviewReceipt? review;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachOperationReceipt &&
          other.kind == kind &&
          other.operationId == operationId &&
          other.paper == paper &&
          other.review == review;

  @override
  int get hashCode =>
      kind.hashCode +
      operationId.hashCode +
      (paper == null ? 0 : paper.hashCode) +
      (review == null ? 0 : review.hashCode);

  factory ExamCoachOperationReceipt.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachOperationReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachOperationReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ExamCoachOperationReceiptKindEnum {
  @JsonValue(r'paper')
  paper(r'paper'),
  @JsonValue(r'answer_review')
  answerReview(r'answer_review'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ExamCoachOperationReceiptKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
