//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_exit_report_evidence.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicExitReportEvidence {
  /// Returns a new [AcademicExitReportEvidence] instance.
  AcademicExitReportEvidence({
    required this.id,

    required this.yearName,

    required this.termName,

    required this.releasedAt,

    required this.sha256,

    required this.fingerprint,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'year_name', required: true, includeIfNull: false)
  final String yearName;

  @JsonKey(name: r'term_name', required: true, includeIfNull: true)
  final String? termName;

  @JsonKey(name: r'released_at', required: true, includeIfNull: false)
  final DateTime releasedAt;

  @JsonKey(name: r'sha256', required: true, includeIfNull: true)
  final String? sha256;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicExitReportEvidence &&
          other.id == id &&
          other.yearName == yearName &&
          other.termName == termName &&
          other.releasedAt == releasedAt &&
          other.sha256 == sha256 &&
          other.fingerprint == fingerprint;

  @override
  int get hashCode =>
      id.hashCode +
      yearName.hashCode +
      (termName == null ? 0 : termName.hashCode) +
      releasedAt.hashCode +
      (sha256 == null ? 0 : sha256.hashCode) +
      fingerprint.hashCode;

  factory AcademicExitReportEvidence.fromJson(Map<String, dynamic> json) =>
      _$AcademicExitReportEvidenceFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicExitReportEvidenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
