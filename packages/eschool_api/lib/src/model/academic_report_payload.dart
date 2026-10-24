//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_subject_result.dart';
import 'package:eschool_api/src/model/academic_report_attendance.dart';
import 'package:eschool_api/src/model/academic_report_enrolment.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_payload.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportPayload {
  /// Returns a new [AcademicReportPayload] instance.
  AcademicReportPayload({
    required this.learnerId,

    required this.learnerName,

    required this.schoolName,

    required this.yearName,

    required this.termName,

    required this.homeroomName,

    required this.gradeName,

    required this.sessionYearId,

    required this.termId,

    required this.classSectionId,

    required this.gradeLevelId,

    required this.startsOn,

    required this.endsOn,

    required this.mode,

    required this.provisional,

    required this.ready,

    required this.subjects,

    required this.attendance,

    required this.enrolments,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(name: r'school_name', required: true, includeIfNull: false)
  final String schoolName;

  @JsonKey(name: r'year_name', required: true, includeIfNull: false)
  final String yearName;

  @JsonKey(name: r'term_name', required: true, includeIfNull: true)
  final String? termName;

  @JsonKey(name: r'homeroom_name', required: true, includeIfNull: false)
  final String homeroomName;

  @JsonKey(name: r'grade_name', required: true, includeIfNull: false)
  final String gradeName;

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

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(name: r'mode', required: true, includeIfNull: false)
  final String mode;

  @JsonKey(name: r'provisional', required: true, includeIfNull: false)
  final bool provisional;

  @JsonKey(name: r'ready', required: true, includeIfNull: false)
  final bool ready;

  @JsonKey(name: r'subjects', required: true, includeIfNull: false)
  final List<AcademicReportSubjectResult> subjects;

  @JsonKey(name: r'attendance', required: true, includeIfNull: false)
  final AcademicReportAttendance attendance;

  @JsonKey(name: r'enrolments', required: true, includeIfNull: false)
  final List<AcademicReportEnrolment> enrolments;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportPayload &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.schoolName == schoolName &&
          other.yearName == yearName &&
          other.termName == termName &&
          other.homeroomName == homeroomName &&
          other.gradeName == gradeName &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.classSectionId == classSectionId &&
          other.gradeLevelId == gradeLevelId &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.mode == mode &&
          other.provisional == provisional &&
          other.ready == ready &&
          other.subjects == subjects &&
          other.attendance == attendance &&
          other.enrolments == enrolments;

  @override
  int get hashCode =>
      learnerId.hashCode +
      learnerName.hashCode +
      schoolName.hashCode +
      yearName.hashCode +
      (termName == null ? 0 : termName.hashCode) +
      homeroomName.hashCode +
      gradeName.hashCode +
      sessionYearId.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      classSectionId.hashCode +
      gradeLevelId.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      mode.hashCode +
      provisional.hashCode +
      ready.hashCode +
      subjects.hashCode +
      attendance.hashCode +
      enrolments.hashCode;

  factory AcademicReportPayload.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportPayloadFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportPayloadToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
