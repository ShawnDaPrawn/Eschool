//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_selection.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportSelection {
  /// Returns a new [AcademicReportSelection] instance.
  AcademicReportSelection({
    required this.templateId,

    required this.sessionYearId,

    required this.termId,

    required this.classSectionId,

    required this.learnerIds,

    required this.mode,
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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportSelection &&
          other.templateId == templateId &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.classSectionId == classSectionId &&
          other.learnerIds == learnerIds &&
          other.mode == mode;

  @override
  int get hashCode =>
      templateId.hashCode +
      sessionYearId.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      classSectionId.hashCode +
      learnerIds.hashCode +
      mode.hashCode;

  factory AcademicReportSelection.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportSelectionFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportSelectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
