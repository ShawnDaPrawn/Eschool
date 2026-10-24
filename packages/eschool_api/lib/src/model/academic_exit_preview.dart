//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_exit_issue.dart';
import 'package:eschool_api/src/model/academic_exit_selection.dart';
import 'package:eschool_api/src/model/academic_exit_report_evidence.dart';
import 'package:eschool_api/src/model/academic_exit_document_evidence.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_exit_preview.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicExitPreview {
  /// Returns a new [AcademicExitPreview] instance.
  AcademicExitPreview({
    required this.selection,

    required this.learnerId,

    required this.learnerName,

    required this.lastAttendedOn,

    required this.attendanceThrough,

    required this.reports,

    required this.documents,

    required this.releasedMarkCount,

    required this.attendanceCount,

    required this.enrolmentCount,

    required this.fingerprint,

    required this.canPrepare,

    required this.issues,

    required this.estimatedBytes,

    required this.retentionDays,
  });

  @JsonKey(name: r'selection', required: true, includeIfNull: false)
  final AcademicExitSelection selection;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(name: r'last_attended_on', required: true, includeIfNull: false)
  final String lastAttendedOn;

  @JsonKey(name: r'attendance_through', required: true, includeIfNull: false)
  final String attendanceThrough;

  @JsonKey(name: r'reports', required: true, includeIfNull: false)
  final List<AcademicExitReportEvidence> reports;

  @JsonKey(name: r'documents', required: true, includeIfNull: false)
  final List<AcademicExitDocumentEvidence> documents;

  @JsonKey(name: r'released_mark_count', required: true, includeIfNull: false)
  final int releasedMarkCount;

  @JsonKey(name: r'attendance_count', required: true, includeIfNull: false)
  final int attendanceCount;

  @JsonKey(name: r'enrolment_count', required: true, includeIfNull: false)
  final int enrolmentCount;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'can_prepare', required: true, includeIfNull: false)
  final bool canPrepare;

  @JsonKey(name: r'issues', required: true, includeIfNull: false)
  final List<AcademicExitIssue> issues;

  @JsonKey(name: r'estimated_bytes', required: true, includeIfNull: false)
  final int estimatedBytes;

  @JsonKey(name: r'retention_days', required: true, includeIfNull: false)
  final int retentionDays;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicExitPreview &&
          other.selection == selection &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.lastAttendedOn == lastAttendedOn &&
          other.attendanceThrough == attendanceThrough &&
          other.reports == reports &&
          other.documents == documents &&
          other.releasedMarkCount == releasedMarkCount &&
          other.attendanceCount == attendanceCount &&
          other.enrolmentCount == enrolmentCount &&
          other.fingerprint == fingerprint &&
          other.canPrepare == canPrepare &&
          other.issues == issues &&
          other.estimatedBytes == estimatedBytes &&
          other.retentionDays == retentionDays;

  @override
  int get hashCode =>
      selection.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      lastAttendedOn.hashCode +
      attendanceThrough.hashCode +
      reports.hashCode +
      documents.hashCode +
      releasedMarkCount.hashCode +
      attendanceCount.hashCode +
      enrolmentCount.hashCode +
      fingerprint.hashCode +
      canPrepare.hashCode +
      issues.hashCode +
      estimatedBytes.hashCode +
      retentionDays.hashCode;

  factory AcademicExitPreview.fromJson(Map<String, dynamic> json) =>
      _$AcademicExitPreviewFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicExitPreviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
