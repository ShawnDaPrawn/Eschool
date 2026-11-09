//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_question.dart';
import 'package:eschool_api/src/model/practice_test_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_sitting.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestSitting {
  /// Returns a new [PracticeTestSitting] instance.
  PracticeTestSitting({
    required this.id,

    required this.learnerId,

    required this.groupId,

    required this.blueprintId,

    required this.kind,

    required this.title,

    required this.state,

    required this.serverTime,

    required this.startedAt,

    required this.deadlineAt,

    required this.secondsLeft,

    required this.timeLimitSeconds,

    required this.submittedAt,

    required this.closedBy,

    required this.questions,

    required this.result,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'blueprint_id', required: true, includeIfNull: false)
  final int blueprintId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeTestSittingKindEnum.unknownDefaultOpenApi,
  )
  final PracticeTestSittingKindEnum kind;

  @JsonKey(name: r'title', required: true, includeIfNull: true)
  final String? title;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeTestSittingStateEnum.unknownDefaultOpenApi,
  )
  final PracticeTestSittingStateEnum state;

  @JsonKey(name: r'server_time', required: true, includeIfNull: false)
  final DateTime serverTime;

  @JsonKey(name: r'started_at', required: true, includeIfNull: false)
  final DateTime startedAt;

  @JsonKey(name: r'deadline_at', required: true, includeIfNull: false)
  final DateTime deadlineAt;

  // minimum: 0
  @JsonKey(name: r'seconds_left', required: true, includeIfNull: false)
  final int secondsLeft;

  // minimum: 1
  @JsonKey(name: r'time_limit_seconds', required: true, includeIfNull: false)
  final int timeLimitSeconds;

  @JsonKey(name: r'submitted_at', required: true, includeIfNull: true)
  final DateTime? submittedAt;

  @JsonKey(
    name: r'closed_by',
    required: true,
    includeIfNull: true,
    unknownEnumValue: PracticeTestSittingClosedByEnum.unknownDefaultOpenApi,
  )
  final PracticeTestSittingClosedByEnum? closedBy;

  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final List<PracticeTestQuestion> questions;

  @JsonKey(name: r'result', required: true, includeIfNull: true)
  final PracticeTestResult? result;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestSitting &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.groupId == groupId &&
          other.blueprintId == blueprintId &&
          other.kind == kind &&
          other.title == title &&
          other.state == state &&
          other.serverTime == serverTime &&
          other.startedAt == startedAt &&
          other.deadlineAt == deadlineAt &&
          other.secondsLeft == secondsLeft &&
          other.timeLimitSeconds == timeLimitSeconds &&
          other.submittedAt == submittedAt &&
          other.closedBy == closedBy &&
          other.questions == questions &&
          other.result == result;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      groupId.hashCode +
      blueprintId.hashCode +
      kind.hashCode +
      (title == null ? 0 : title.hashCode) +
      state.hashCode +
      serverTime.hashCode +
      startedAt.hashCode +
      deadlineAt.hashCode +
      secondsLeft.hashCode +
      timeLimitSeconds.hashCode +
      (submittedAt == null ? 0 : submittedAt.hashCode) +
      (closedBy == null ? 0 : closedBy.hashCode) +
      questions.hashCode +
      (result == null ? 0 : result.hashCode);

  factory PracticeTestSitting.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestSittingFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestSittingToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeTestSittingKindEnum {
  @JsonValue(r'quick_quiz')
  quickQuiz(r'quick_quiz'),
  @JsonValue(r'cycle_test')
  cycleTest(r'cycle_test'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestSittingKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PracticeTestSittingStateEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'time_up')
  timeUp(r'time_up'),
  @JsonValue(r'handed_in')
  handedIn(r'handed_in'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestSittingStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PracticeTestSittingClosedByEnum {
  @JsonValue(r'learner')
  learner(r'learner'),
  @JsonValue(r'time')
  time(r'time'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestSittingClosedByEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
