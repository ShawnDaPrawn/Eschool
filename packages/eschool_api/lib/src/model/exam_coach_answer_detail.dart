//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_answer_review.dart';
import 'package:eschool_api/src/model/exam_coach_answer_feedback.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_answer_detail.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachAnswerDetail {
  /// Returns a new [ExamCoachAnswerDetail] instance.
  ExamCoachAnswerDetail({
    required this.id,

    required this.operationId,

    required this.state,

    required this.failureCode,

    this.groupId,

    this.paperVersionId,

    this.questionNumber,

    this.revision,

    this.expiresAt,

    this.working,

    required this.response,

    required this.review,

    this.provisional,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ExamCoachAnswerDetailStateEnum.unknownDefaultOpenApi,
  )
  final ExamCoachAnswerDetailStateEnum state;

  @JsonKey(name: r'failure_code', required: true, includeIfNull: true)
  final String? failureCode;

  // minimum: 1
  @JsonKey(name: r'group_id', required: false, includeIfNull: false)
  final int? groupId;

  @JsonKey(name: r'paper_version_id', required: false, includeIfNull: false)
  final String? paperVersionId;

  @JsonKey(name: r'question_number', required: false, includeIfNull: false)
  final String? questionNumber;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  @JsonKey(name: r'expires_at', required: false, includeIfNull: false)
  final DateTime? expiresAt;

  @JsonKey(name: r'working', required: false, includeIfNull: false)
  final String? working;

  @JsonKey(name: r'response', required: true, includeIfNull: true)
  final ExamCoachAnswerFeedback? response;

  @JsonKey(name: r'review', required: true, includeIfNull: true)
  final ExamCoachAnswerReview? review;

  @JsonKey(name: r'provisional', required: false, includeIfNull: false)
  final bool? provisional;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachAnswerDetail &&
          other.id == id &&
          other.operationId == operationId &&
          other.state == state &&
          other.failureCode == failureCode &&
          other.groupId == groupId &&
          other.paperVersionId == paperVersionId &&
          other.questionNumber == questionNumber &&
          other.revision == revision &&
          other.expiresAt == expiresAt &&
          other.working == working &&
          other.response == response &&
          other.review == review &&
          other.provisional == provisional;

  @override
  int get hashCode =>
      id.hashCode +
      operationId.hashCode +
      state.hashCode +
      (failureCode == null ? 0 : failureCode.hashCode) +
      groupId.hashCode +
      paperVersionId.hashCode +
      questionNumber.hashCode +
      revision.hashCode +
      expiresAt.hashCode +
      working.hashCode +
      (response == null ? 0 : response.hashCode) +
      (review == null ? 0 : review.hashCode) +
      provisional.hashCode;

  factory ExamCoachAnswerDetail.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachAnswerDetailFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachAnswerDetailToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ExamCoachAnswerDetailStateEnum {
  @JsonValue(r'prepared')
  prepared(r'prepared'),
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'failed')
  failed(r'failed'),
  @JsonValue(r'quarantined')
  quarantined(r'quarantined'),
  @JsonValue(r'purged')
  purged(r'purged'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ExamCoachAnswerDetailStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
