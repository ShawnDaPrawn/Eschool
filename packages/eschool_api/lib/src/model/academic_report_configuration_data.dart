//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_template.dart';
import 'package:eschool_api/src/model/academic_report_term.dart';
import 'package:eschool_api/src/model/academic_report_comment_bank.dart';
import 'package:eschool_api/src/model/academic_report_homeroom.dart';
import 'package:eschool_api/src/model/academic_timetable_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_configuration_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportConfigurationData {
  /// Returns a new [AcademicReportConfigurationData] instance.
  AcademicReportConfigurationData({
    required this.sessionYearId,

    required this.schoolYears,

    required this.homerooms,

    required this.terms,

    required this.templates,

    required this.canDesign,

    required this.commentBanks,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'school_years', required: true, includeIfNull: false)
  final List<AcademicTimetableChoice> schoolYears;

  @JsonKey(name: r'homerooms', required: true, includeIfNull: false)
  final List<AcademicReportHomeroom> homerooms;

  @JsonKey(name: r'terms', required: true, includeIfNull: false)
  final List<AcademicReportTerm> terms;

  @JsonKey(name: r'templates', required: true, includeIfNull: false)
  final List<AcademicReportTemplate> templates;

  @JsonKey(name: r'can_design', required: true, includeIfNull: false)
  final bool canDesign;

  @JsonKey(name: r'comment_banks', required: true, includeIfNull: false)
  final List<AcademicReportCommentBank> commentBanks;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportConfigurationData &&
          other.sessionYearId == sessionYearId &&
          other.schoolYears == schoolYears &&
          other.homerooms == homerooms &&
          other.terms == terms &&
          other.templates == templates &&
          other.canDesign == canDesign &&
          other.commentBanks == commentBanks;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      schoolYears.hashCode +
      homerooms.hashCode +
      terms.hashCode +
      templates.hashCode +
      canDesign.hashCode +
      commentBanks.hashCode;

  factory AcademicReportConfigurationData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportConfigurationDataFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicReportConfigurationDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
