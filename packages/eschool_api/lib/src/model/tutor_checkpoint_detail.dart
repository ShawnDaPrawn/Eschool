//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/tutor_checkpoint_question.dart';
import 'package:json_annotation/json_annotation.dart';

part 'tutor_checkpoint_detail.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorCheckpointDetail {
  /// Returns a new [TutorCheckpointDetail] instance.
  TutorCheckpointDetail({
    required this.schoolId,

    required this.readerId,

    required this.id,

    required this.sessionId,

    required this.learnerId,

    required this.topicId,

    required this.operationId,

    required this.answerOperationId,

    required this.state,

    required this.question,

    required this.correct,

    required this.explanation,

    required this.answeredAt,

    required this.verifiedBy,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'session_id', required: true, includeIfNull: false)
  final String sessionId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'answer_operation_id', required: true, includeIfNull: true)
  final String? answerOperationId;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TutorCheckpointDetailStateEnum.unknownDefaultOpenApi,
  )
  final TutorCheckpointDetailStateEnum state;

  @JsonKey(name: r'question', required: true, includeIfNull: false)
  final TutorCheckpointQuestion question;

  @JsonKey(name: r'correct', required: true, includeIfNull: true)
  final bool? correct;

  @JsonKey(name: r'explanation', required: true, includeIfNull: true)
  final String? explanation;

  @JsonKey(name: r'answered_at', required: true, includeIfNull: true)
  final DateTime? answeredAt;

  /// When answered, approved_answer_definition identifies deterministic grading of the first attempt. Never AI confidence or an official mark.
  @JsonKey(name: r'verified_by', required: true, includeIfNull: true)
  final String? verifiedBy;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorCheckpointDetail &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.id == id &&
          other.sessionId == sessionId &&
          other.learnerId == learnerId &&
          other.topicId == topicId &&
          other.operationId == operationId &&
          other.answerOperationId == answerOperationId &&
          other.state == state &&
          other.question == question &&
          other.correct == correct &&
          other.explanation == explanation &&
          other.answeredAt == answeredAt &&
          other.verifiedBy == verifiedBy;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      id.hashCode +
      sessionId.hashCode +
      learnerId.hashCode +
      topicId.hashCode +
      operationId.hashCode +
      (answerOperationId == null ? 0 : answerOperationId.hashCode) +
      state.hashCode +
      question.hashCode +
      (correct == null ? 0 : correct.hashCode) +
      (explanation == null ? 0 : explanation.hashCode) +
      (answeredAt == null ? 0 : answeredAt.hashCode) +
      (verifiedBy == null ? 0 : verifiedBy.hashCode);

  factory TutorCheckpointDetail.fromJson(Map<String, dynamic> json) =>
      _$TutorCheckpointDetailFromJson(json);

  Map<String, dynamic> toJson() => _$TutorCheckpointDetailToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TutorCheckpointDetailStateEnum {
  @JsonValue(r'ready')
  ready(r'ready'),
  @JsonValue(r'answered')
  answered(r'answered'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorCheckpointDetailStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
