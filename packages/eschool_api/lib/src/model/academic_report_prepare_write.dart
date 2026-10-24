//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_prepare_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportPrepareWrite {
  /// Returns a new [AcademicReportPrepareWrite] instance.
  AcademicReportPrepareWrite({
    required this.templateId,

    required this.sessionYearId,

    required this.termId,

    required this.classSectionId,

    required this.learnerIds,

    required this.mode,

    required this.fingerprint,
  });

  // minimum: 1
  @JsonKey(name: r'template_id', required: true, includeIfNull: false)
  final int templateId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: true)
  final int? termId;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: true, includeIfNull: false)
  final int classSectionId;

  @JsonKey(name: r'learner_ids', required: true, includeIfNull: false)
  final List<int> learnerIds;

  @JsonKey(name: r'mode', required: true, includeIfNull: false)
  final String mode;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportPrepareWrite &&
          other.templateId == templateId &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.classSectionId == classSectionId &&
          other.learnerIds == learnerIds &&
          other.mode == mode &&
          other.fingerprint == fingerprint;

  @override
  int get hashCode =>
      templateId.hashCode +
      sessionYearId.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      classSectionId.hashCode +
      learnerIds.hashCode +
      mode.hashCode +
      fingerprint.hashCode;

  factory AcademicReportPrepareWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportPrepareWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportPrepareWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
