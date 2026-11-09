//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_solution.dart';
import 'package:eschool_api/src/model/practice_schedule.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_review_v2.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeReviewV2 {
  /// Returns a new [PracticeReviewV2] instance.
  PracticeReviewV2({
    required this.id,

    required this.entryId,

    required this.state,

    required this.correct,

    required this.achievedUnits,

    required this.maximumUnits,

    required this.parts,

    required this.markedHow,

    required this.schedule,

    required this.reviewedOn,

    required this.solution,

    this.streakDays,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'entry_id', required: true, includeIfNull: false)
  final int entryId;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeReviewV2StateEnum.unknownDefaultOpenApi,
  )
  final PracticeReviewV2StateEnum state;

  @JsonKey(name: r'correct', required: true, includeIfNull: true)
  final bool? correct;

  // minimum: 0
  // maximum: 8
  @JsonKey(name: r'achieved_units', required: true, includeIfNull: true)
  final int? achievedUnits;

  // minimum: 1
  // maximum: 8
  @JsonKey(name: r'maximum_units', required: true, includeIfNull: false)
  final int maximumUnits;

  /// The mark of each step or region, by its code.
  @JsonKey(name: r'parts', required: true, includeIfNull: true)
  final Map<String, bool>? parts;

  @JsonKey(
    name: r'marked_how',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeReviewV2MarkedHowEnum.unknownDefaultOpenApi,
  )
  final PracticeReviewV2MarkedHowEnum markedHow;

  @JsonKey(name: r'schedule', required: true, includeIfNull: true)
  final PracticeSchedule? schedule;

  @JsonKey(name: r'reviewed_on', required: true, includeIfNull: false)
  final String reviewedOn;

  @JsonKey(name: r'solution', required: true, includeIfNull: false)
  final PracticeSolution solution;

  // minimum: 0
  // maximum: 365
  @JsonKey(name: r'streak_days', required: false, includeIfNull: false)
  final int? streakDays;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeReviewV2 &&
          other.id == id &&
          other.entryId == entryId &&
          other.state == state &&
          other.correct == correct &&
          other.achievedUnits == achievedUnits &&
          other.maximumUnits == maximumUnits &&
          other.parts == parts &&
          other.markedHow == markedHow &&
          other.schedule == schedule &&
          other.reviewedOn == reviewedOn &&
          other.solution == solution &&
          other.streakDays == streakDays;

  @override
  int get hashCode =>
      id.hashCode +
      entryId.hashCode +
      state.hashCode +
      (correct == null ? 0 : correct.hashCode) +
      (achievedUnits == null ? 0 : achievedUnits.hashCode) +
      maximumUnits.hashCode +
      (parts == null ? 0 : parts.hashCode) +
      markedHow.hashCode +
      (schedule == null ? 0 : schedule.hashCode) +
      reviewedOn.hashCode +
      solution.hashCode +
      streakDays.hashCode;

  factory PracticeReviewV2.fromJson(Map<String, dynamic> json) =>
      _$PracticeReviewV2FromJson(json);

  Map<String, dynamic> toJson() => _$PracticeReviewV2ToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeReviewV2StateEnum {
  @JsonValue(r'marked')
  marked(r'marked'),
  @JsonValue(r'awaiting_teacher')
  awaitingTeacher(r'awaiting_teacher'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeReviewV2StateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PracticeReviewV2MarkedHowEnum {
  @JsonValue(r'automatic')
  automatic(r'automatic'),
  @JsonValue(r'teacher')
  teacher(r'teacher'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeReviewV2MarkedHowEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
