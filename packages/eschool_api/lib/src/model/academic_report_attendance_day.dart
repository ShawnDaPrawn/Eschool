//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_attendance_day.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportAttendanceDay {
  /// Returns a new [AcademicReportAttendanceDay] instance.
  AcademicReportAttendanceDay({
    required this.onDate,

    required this.state,

    required this.revision,
  });

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: true)
  final int? revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportAttendanceDay &&
          other.onDate == onDate &&
          other.state == state &&
          other.revision == revision;

  @override
  int get hashCode =>
      onDate.hashCode +
      state.hashCode +
      (revision == null ? 0 : revision.hashCode);

  factory AcademicReportAttendanceDay.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportAttendanceDayFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportAttendanceDayToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
