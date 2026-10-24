//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_attendance_today_entry.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_attendance_today_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicAttendanceTodayData {
  /// Returns a new [AcademicAttendanceTodayData] instance.
  AcademicAttendanceTodayData({
    required this.learnerId,

    required this.onDate,

    required this.entries,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'entries', required: true, includeIfNull: false)
  final List<AcademicAttendanceTodayEntry> entries;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicAttendanceTodayData &&
          other.learnerId == learnerId &&
          other.onDate == onDate &&
          other.entries == entries;

  @override
  int get hashCode => learnerId.hashCode + onDate.hashCode + entries.hashCode;

  factory AcademicAttendanceTodayData.fromJson(Map<String, dynamic> json) =>
      _$AcademicAttendanceTodayDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicAttendanceTodayDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
