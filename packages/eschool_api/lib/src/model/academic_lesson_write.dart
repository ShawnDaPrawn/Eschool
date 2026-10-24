//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_lesson_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicLessonWrite {
  /// Returns a new [AcademicLessonWrite] instance.
  AcademicLessonWrite({
    this.id,

    this.revision,

    required this.sessionYearId,

    this.termId,

    this.teachingGroupId,

    this.classSectionId,

    required this.teacherId,

    required this.roomId,

    required this.bellPeriodId,

    required this.startsOn,

    required this.endsOn,

    this.occursOn,

    required this.weekday,

    required this.weekPattern,

    required this.active,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: false, includeIfNull: false)
  final int? id;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'term_id', required: false, includeIfNull: false)
  final int? termId;

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: false, includeIfNull: false)
  final int? teachingGroupId;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: false, includeIfNull: false)
  final int? classSectionId;

  // minimum: 1
  @JsonKey(name: r'teacher_id', required: true, includeIfNull: false)
  final int teacherId;

  // minimum: 1
  @JsonKey(name: r'room_id', required: true, includeIfNull: false)
  final int roomId;

  // minimum: 1
  @JsonKey(name: r'bell_period_id', required: true, includeIfNull: false)
  final int bellPeriodId;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(name: r'occurs_on', required: false, includeIfNull: false)
  final String? occursOn;

  // minimum: 1
  // maximum: 7
  @JsonKey(name: r'weekday', required: true, includeIfNull: false)
  final int weekday;

  @JsonKey(
    name: r'week_pattern',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicLessonWriteWeekPatternEnum.unknownDefaultOpenApi,
  )
  final AcademicLessonWriteWeekPatternEnum weekPattern;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicLessonWrite &&
          other.id == id &&
          other.revision == revision &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.teachingGroupId == teachingGroupId &&
          other.classSectionId == classSectionId &&
          other.teacherId == teacherId &&
          other.roomId == roomId &&
          other.bellPeriodId == bellPeriodId &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.occursOn == occursOn &&
          other.weekday == weekday &&
          other.weekPattern == weekPattern &&
          other.active == active;

  @override
  int get hashCode =>
      (id == null ? 0 : id.hashCode) +
      (revision == null ? 0 : revision.hashCode) +
      sessionYearId.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      (teachingGroupId == null ? 0 : teachingGroupId.hashCode) +
      (classSectionId == null ? 0 : classSectionId.hashCode) +
      teacherId.hashCode +
      roomId.hashCode +
      bellPeriodId.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      (occursOn == null ? 0 : occursOn.hashCode) +
      weekday.hashCode +
      weekPattern.hashCode +
      active.hashCode;

  factory AcademicLessonWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicLessonWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicLessonWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicLessonWriteWeekPatternEnum {
  @JsonValue(r'all')
  all(r'all'),
  @JsonValue(r'odd')
  odd(r'odd'),
  @JsonValue(r'even')
  even(r'even'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicLessonWriteWeekPatternEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
