//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_register_settings_data.dart';
import 'package:eschool_api/src/model/academic_timetable_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_choices_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterChoicesData {
  /// Returns a new [AcademicRegisterChoicesData] instance.
  AcademicRegisterChoicesData({
    required this.onDate,

    required this.sessionYearId,

    required this.schoolYears,

    required this.homerooms,

    required this.periods,

    required this.canManage,

    required this.settings,
  });

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'school_years', required: true, includeIfNull: false)
  final List<AcademicTimetableChoice> schoolYears;

  @JsonKey(name: r'homerooms', required: true, includeIfNull: false)
  final List<AcademicTimetableChoice> homerooms;

  @JsonKey(name: r'periods', required: true, includeIfNull: false)
  final List<AcademicTimetableChoice> periods;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'settings', required: true, includeIfNull: false)
  final AcademicRegisterSettingsData settings;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterChoicesData &&
          other.onDate == onDate &&
          other.sessionYearId == sessionYearId &&
          other.schoolYears == schoolYears &&
          other.homerooms == homerooms &&
          other.periods == periods &&
          other.canManage == canManage &&
          other.settings == settings;

  @override
  int get hashCode =>
      onDate.hashCode +
      sessionYearId.hashCode +
      schoolYears.hashCode +
      homerooms.hashCode +
      periods.hashCode +
      canManage.hashCode +
      settings.hashCode;

  factory AcademicRegisterChoicesData.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterChoicesDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRegisterChoicesDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
