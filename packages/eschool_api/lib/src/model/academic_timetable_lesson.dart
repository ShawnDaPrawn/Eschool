//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_lesson.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableLesson {
  /// Returns a new [AcademicTimetableLesson] instance.
  AcademicTimetableLesson({
    this.coverId,

    this.originalTeacherId,

    this.originalTeacherName,

    required this.id,

    required this.revision,

    required this.sessionYearId,

    required this.termId,

    required this.teachingGroupId,

    required this.classSectionId,

    required this.teacherId,

    required this.roomId,

    required this.bellPeriodId,

    required this.startsOn,

    required this.endsOn,

    required this.occursOn,

    required this.weekday,

    required this.weekPattern,

    required this.active,

    required this.teacherName,

    required this.roomName,

    required this.building,

    required this.periodName,

    required this.startsAt,

    required this.endsAt,

    required this.title,

    required this.subjectName,

    required this.date,
  });

  // minimum: 1
  @JsonKey(name: r'cover_id', required: false, includeIfNull: false)
  final int? coverId;

  // minimum: 1
  @JsonKey(name: r'original_teacher_id', required: false, includeIfNull: false)
  final int? originalTeacherId;

  @JsonKey(
    name: r'original_teacher_name',
    required: false,
    includeIfNull: false,
  )
  final String? originalTeacherName;

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: true)
  final int? termId;

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: true, includeIfNull: true)
  final int? teachingGroupId;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: true, includeIfNull: true)
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

  @JsonKey(name: r'occurs_on', required: true, includeIfNull: true)
  final String? occursOn;

  // minimum: 1
  // maximum: 7
  @JsonKey(name: r'weekday', required: true, includeIfNull: false)
  final int weekday;

  @JsonKey(
    name: r'week_pattern',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        AcademicTimetableLessonWeekPatternEnum.unknownDefaultOpenApi,
  )
  final AcademicTimetableLessonWeekPatternEnum weekPattern;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @JsonKey(name: r'teacher_name', required: true, includeIfNull: false)
  final String teacherName;

  @JsonKey(name: r'room_name', required: true, includeIfNull: false)
  final String roomName;

  @JsonKey(name: r'building', required: true, includeIfNull: true)
  final String? building;

  @JsonKey(name: r'period_name', required: true, includeIfNull: false)
  final String periodName;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: true)
  final String? subjectName;

  @JsonKey(name: r'date', required: true, includeIfNull: true)
  final String? date;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableLesson &&
          other.coverId == coverId &&
          other.originalTeacherId == originalTeacherId &&
          other.originalTeacherName == originalTeacherName &&
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
          other.active == active &&
          other.teacherName == teacherName &&
          other.roomName == roomName &&
          other.building == building &&
          other.periodName == periodName &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.title == title &&
          other.subjectName == subjectName &&
          other.date == date;

  @override
  int get hashCode =>
      coverId.hashCode +
      originalTeacherId.hashCode +
      originalTeacherName.hashCode +
      id.hashCode +
      revision.hashCode +
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
      active.hashCode +
      teacherName.hashCode +
      roomName.hashCode +
      (building == null ? 0 : building.hashCode) +
      periodName.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      title.hashCode +
      (subjectName == null ? 0 : subjectName.hashCode) +
      (date == null ? 0 : date.hashCode);

  factory AcademicTimetableLesson.fromJson(Map<String, dynamic> json) =>
      _$AcademicTimetableLessonFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTimetableLessonToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicTimetableLessonWeekPatternEnum {
  @JsonValue(r'all')
  all(r'all'),
  @JsonValue(r'odd')
  odd(r'odd'),
  @JsonValue(r'even')
  even(r'even'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicTimetableLessonWeekPatternEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
