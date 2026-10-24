//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_subject_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportSubjectResult {
  /// Returns a new [AcademicReportSubjectResult] instance.
  AcademicReportSubjectResult({
    required this.subjectId,

    required this.subjectName,

    required this.snapshotId,

    required this.fingerprint,

    required this.percentageBp,

    required this.ratingLevel,

    required this.coverageBp,

    required this.complete,

    required this.ready,

    required this.resultStatus,
  });

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  // minimum: 1
  @JsonKey(name: r'snapshot_id', required: true, includeIfNull: true)
  final int? snapshotId;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: true)
  final String? fingerprint;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  // minimum: 1
  // maximum: 7
  @JsonKey(name: r'rating_level', required: true, includeIfNull: true)
  final int? ratingLevel;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'coverage_bp', required: true, includeIfNull: false)
  final int coverageBp;

  @JsonKey(name: r'complete', required: true, includeIfNull: false)
  final bool complete;

  @JsonKey(name: r'ready', required: true, includeIfNull: false)
  final bool ready;

  @JsonKey(name: r'result_status', required: true, includeIfNull: false)
  final String resultStatus;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportSubjectResult &&
          other.subjectId == subjectId &&
          other.subjectName == subjectName &&
          other.snapshotId == snapshotId &&
          other.fingerprint == fingerprint &&
          other.percentageBp == percentageBp &&
          other.ratingLevel == ratingLevel &&
          other.coverageBp == coverageBp &&
          other.complete == complete &&
          other.ready == ready &&
          other.resultStatus == resultStatus;

  @override
  int get hashCode =>
      subjectId.hashCode +
      subjectName.hashCode +
      (snapshotId == null ? 0 : snapshotId.hashCode) +
      (fingerprint == null ? 0 : fingerprint.hashCode) +
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      (ratingLevel == null ? 0 : ratingLevel.hashCode) +
      coverageBp.hashCode +
      complete.hashCode +
      ready.hashCode +
      resultStatus.hashCode;

  factory AcademicReportSubjectResult.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportSubjectResultFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportSubjectResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
