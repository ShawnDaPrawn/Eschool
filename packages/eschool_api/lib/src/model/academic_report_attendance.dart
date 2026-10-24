//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_attendance_day.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_attendance.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportAttendance {
  /// Returns a new [AcademicReportAttendance] instance.
  AcademicReportAttendance({
    required this.daysRecorded,

    required this.presentHalfDays,

    required this.absentHalfDays,

    required this.lateDays,

    required this.excusedDays,

    required this.evidence,
  });

  // minimum: 0
  @JsonKey(name: r'days_recorded', required: true, includeIfNull: false)
  final int daysRecorded;

  // minimum: 0
  @JsonKey(name: r'present_half_days', required: true, includeIfNull: false)
  final int presentHalfDays;

  // minimum: 0
  @JsonKey(name: r'absent_half_days', required: true, includeIfNull: false)
  final int absentHalfDays;

  // minimum: 0
  @JsonKey(name: r'late_days', required: true, includeIfNull: false)
  final int lateDays;

  // minimum: 0
  @JsonKey(name: r'excused_days', required: true, includeIfNull: false)
  final int excusedDays;

  @JsonKey(name: r'evidence', required: true, includeIfNull: false)
  final List<AcademicReportAttendanceDay> evidence;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportAttendance &&
          other.daysRecorded == daysRecorded &&
          other.presentHalfDays == presentHalfDays &&
          other.absentHalfDays == absentHalfDays &&
          other.lateDays == lateDays &&
          other.excusedDays == excusedDays &&
          other.evidence == evidence;

  @override
  int get hashCode =>
      daysRecorded.hashCode +
      presentHalfDays.hashCode +
      absentHalfDays.hashCode +
      lateDays.hashCode +
      excusedDays.hashCode +
      evidence.hashCode;

  factory AcademicReportAttendance.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportAttendanceFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportAttendanceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
