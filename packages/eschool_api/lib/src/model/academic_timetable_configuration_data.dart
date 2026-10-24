//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_bells.dart';
import 'package:eschool_api/src/model/academic_room.dart';
import 'package:eschool_api/src/model/academic_timetable_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_configuration_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableConfigurationData {
  /// Returns a new [AcademicTimetableConfigurationData] instance.
  AcademicTimetableConfigurationData({
    this.schoolYears,

    required this.rooms,

    required this.bellSchedules,

    required this.teachers,

    required this.homerooms,
  });

  /// Calendars available to managers for explicit week editing.
  @JsonKey(name: r'school_years', required: false, includeIfNull: false)
  final List<AcademicTimetableChoice>? schoolYears;

  @JsonKey(name: r'rooms', required: true, includeIfNull: false)
  final List<AcademicRoom> rooms;

  @JsonKey(name: r'bell_schedules', required: true, includeIfNull: false)
  final List<AcademicBells> bellSchedules;

  @JsonKey(name: r'teachers', required: true, includeIfNull: false)
  final List<AcademicTimetableChoice> teachers;

  @JsonKey(name: r'homerooms', required: true, includeIfNull: false)
  final List<AcademicTimetableChoice> homerooms;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableConfigurationData &&
          other.schoolYears == schoolYears &&
          other.rooms == rooms &&
          other.bellSchedules == bellSchedules &&
          other.teachers == teachers &&
          other.homerooms == homerooms;

  @override
  int get hashCode =>
      schoolYears.hashCode +
      rooms.hashCode +
      bellSchedules.hashCode +
      teachers.hashCode +
      homerooms.hashCode;

  factory AcademicTimetableConfigurationData.fromJson(
    Map<String, dynamic> json,
  ) => _$AcademicTimetableConfigurationDataFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicTimetableConfigurationDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
