//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_answer_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachAnswerSummary {
  /// Returns a new [ExamCoachAnswerSummary] instance.
  ExamCoachAnswerSummary({
    required this.id,

    required this.operationId,

    required this.state,

    required this.failureCode,

    required this.groupId,

    required this.paperVersionId,

    required this.questionNumber,

    required this.revision,

    required this.expiresAt,

    this.learnerId,

    this.learnerName,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ExamCoachAnswerSummaryStateEnum.unknownDefaultOpenApi,
  )
  final ExamCoachAnswerSummaryStateEnum state;

  @JsonKey(name: r'failure_code', required: true, includeIfNull: true)
  final String? failureCode;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'paper_version_id', required: true, includeIfNull: false)
  final String paperVersionId;

  @JsonKey(name: r'question_number', required: true, includeIfNull: false)
  final String questionNumber;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: false, includeIfNull: false)
  final int? learnerId;

  @JsonKey(name: r'learner_name', required: false, includeIfNull: false)
  final String? learnerName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachAnswerSummary &&
          other.id == id &&
          other.operationId == operationId &&
          other.state == state &&
          other.failureCode == failureCode &&
          other.groupId == groupId &&
          other.paperVersionId == paperVersionId &&
          other.questionNumber == questionNumber &&
          other.revision == revision &&
          other.expiresAt == expiresAt &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName;

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
      learnerId.hashCode +
      learnerName.hashCode;

  factory ExamCoachAnswerSummary.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachAnswerSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachAnswerSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ExamCoachAnswerSummaryStateEnum {
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

  const ExamCoachAnswerSummaryStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
