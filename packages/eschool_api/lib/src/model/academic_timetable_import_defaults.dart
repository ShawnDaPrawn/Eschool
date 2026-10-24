//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_import_defaults.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableImportDefaults {
  /// Returns a new [AcademicTimetableImportDefaults] instance.
  AcademicTimetableImportDefaults({
    this.id,

    this.revision,

    this.sessionYearId,

    this.termId,

    this.teachingGroupId,

    this.classSectionId,

    this.teacherId,

    this.roomId,

    this.bellPeriodId,

    this.startsOn,

    this.endsOn,

    this.occursOn,

    this.weekday,

    this.weekPattern,

    this.active,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: false, includeIfNull: false)
  final int? id;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: false, includeIfNull: false)
  final int? sessionYearId;

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
  @JsonKey(name: r'teacher_id', required: false, includeIfNull: false)
  final int? teacherId;

  // minimum: 1
  @JsonKey(name: r'room_id', required: false, includeIfNull: false)
  final int? roomId;

  // minimum: 1
  @JsonKey(name: r'bell_period_id', required: false, includeIfNull: false)
  final int? bellPeriodId;

  @JsonKey(name: r'starts_on', required: false, includeIfNull: false)
  final String? startsOn;

  @JsonKey(name: r'ends_on', required: false, includeIfNull: false)
  final String? endsOn;

  @JsonKey(name: r'occurs_on', required: false, includeIfNull: false)
  final String? occursOn;

  // minimum: 1
  // maximum: 7
  @JsonKey(name: r'weekday', required: false, includeIfNull: false)
  final int? weekday;

  @JsonKey(
    name: r'week_pattern',
    required: false,
    includeIfNull: false,
    unknownEnumValue:
        AcademicTimetableImportDefaultsWeekPatternEnum.unknownDefaultOpenApi,
  )
  final AcademicTimetableImportDefaultsWeekPatternEnum? weekPattern;

  @JsonKey(name: r'active', required: false, includeIfNull: false)
  final bool? active;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableImportDefaults &&
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

  factory AcademicTimetableImportDefaults.fromJson(Map<String, dynamic> json) =>
      _$AcademicTimetableImportDefaultsFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicTimetableImportDefaultsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicTimetableImportDefaultsWeekPatternEnum {
  @JsonValue(r'all')
  all(r'all'),
  @JsonValue(r'odd')
  odd(r'odd'),
  @JsonValue(r'even')
  even(r'even'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicTimetableImportDefaultsWeekPatternEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
