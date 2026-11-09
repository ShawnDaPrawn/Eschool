//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_learner_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestLearnerResult {
  /// Returns a new [PracticeTestLearnerResult] instance.
  PracticeTestLearnerResult({
    required this.learnerId,

    required this.name,

    required this.state,

    required this.correctCount,

    required this.markedCount,

    required this.closedBy,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeTestLearnerResultStateEnum.unknownDefaultOpenApi,
  )
  final PracticeTestLearnerResultStateEnum state;

  @JsonKey(name: r'correct_count', required: true, includeIfNull: true)
  final int? correctCount;

  @JsonKey(name: r'marked_count', required: true, includeIfNull: true)
  final int? markedCount;

  @JsonKey(
    name: r'closed_by',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        PracticeTestLearnerResultClosedByEnum.unknownDefaultOpenApi,
  )
  final PracticeTestLearnerResultClosedByEnum? closedBy;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestLearnerResult &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.state == state &&
          other.correctCount == correctCount &&
          other.markedCount == markedCount &&
          other.closedBy == closedBy;

  @override
  int get hashCode =>
      learnerId.hashCode +
      name.hashCode +
      state.hashCode +
      (correctCount == null ? 0 : correctCount.hashCode) +
      (markedCount == null ? 0 : markedCount.hashCode) +
      (closedBy == null ? 0 : closedBy.hashCode);

  factory PracticeTestLearnerResult.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestLearnerResultFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestLearnerResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeTestLearnerResultStateEnum {
  @JsonValue(r'not_started')
  notStarted(r'not_started'),
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'time_up')
  timeUp(r'time_up'),
  @JsonValue(r'handed_in')
  handedIn(r'handed_in'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestLearnerResultStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PracticeTestLearnerResultClosedByEnum {
  @JsonValue(r'learner')
  learner(r'learner'),
  @JsonValue(r'time')
  time(r'time'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestLearnerResultClosedByEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
