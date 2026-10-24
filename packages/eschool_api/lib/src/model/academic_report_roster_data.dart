//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_timetable_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_roster_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportRosterData {
  /// Returns a new [AcademicReportRosterData] instance.
  AcademicReportRosterData({
    required this.sessionYearId,

    required this.termId,

    required this.classSectionId,

    required this.gradeLevelId,

    required this.learners,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: true)
  final int? termId;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: true, includeIfNull: false)
  final int classSectionId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<AcademicTimetableChoice> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportRosterData &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.classSectionId == classSectionId &&
          other.gradeLevelId == gradeLevelId &&
          other.learners == learners;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      classSectionId.hashCode +
      gradeLevelId.hashCode +
      learners.hashCode;

  factory AcademicReportRosterData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportRosterDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportRosterDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
