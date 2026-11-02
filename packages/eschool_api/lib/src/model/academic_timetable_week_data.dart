//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_timetable_week_data_activities_inner.dart';
import 'package:eschool_api/src/model/academic_timetable_lesson.dart';
import 'package:eschool_api/src/model/academic_timetable_week_data_breaks_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_week_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableWeekData {
  /// Returns a new [AcademicTimetableWeekData] instance.
  AcademicTimetableWeekData({
    required this.sessionYearId,

    required this.startsOn,

    required this.endsOn,

    required this.canManage,

    required this.lessons,

    this.examDays,

    this.breaks,

    this.activities,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'lessons', required: true, includeIfNull: false)
  final List<AcademicTimetableLesson> lessons;

  @JsonKey(name: r'exam_days', required: false, includeIfNull: false)
  final List<String>? examDays;

  /// A learner's week only. Named breaks of the bells their lessons follow each day (the replacement bells on a short day).
  @JsonKey(name: r'breaks', required: false, includeIfNull: false)
  final List<AcademicTimetableWeekDataBreaksInner>? breaks;

  /// A learner's week only. Sessions of activity groups they belong to on that day, not cancelled. A session without times is all day.
  @JsonKey(name: r'activities', required: false, includeIfNull: false)
  final List<AcademicTimetableWeekDataActivitiesInner>? activities;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableWeekData &&
          other.sessionYearId == sessionYearId &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.canManage == canManage &&
          other.lessons == lessons &&
          other.examDays == examDays &&
          other.breaks == breaks &&
          other.activities == activities;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      canManage.hashCode +
      lessons.hashCode +
      examDays.hashCode +
      breaks.hashCode +
      activities.hashCode;

  factory AcademicTimetableWeekData.fromJson(Map<String, dynamic> json) =>
      _$AcademicTimetableWeekDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTimetableWeekDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
