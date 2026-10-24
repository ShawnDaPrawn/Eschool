//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_batch_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportBatchSummary {
  /// Returns a new [AcademicReportBatchSummary] instance.
  AcademicReportBatchSummary({
    required this.id,

    required this.state,

    required this.mode,

    required this.classSectionId,

    required this.termId,

    required this.learnerCount,

    required this.createdAt,

    required this.releasedAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  @JsonKey(name: r'mode', required: true, includeIfNull: false)
  final String mode;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: true, includeIfNull: false)
  final int classSectionId;

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: true)
  final int? termId;

  // minimum: 0
  @JsonKey(name: r'learner_count', required: true, includeIfNull: false)
  final int learnerCount;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final String createdAt;

  @JsonKey(name: r'released_at', required: true, includeIfNull: true)
  final String? releasedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportBatchSummary &&
          other.id == id &&
          other.state == state &&
          other.mode == mode &&
          other.classSectionId == classSectionId &&
          other.termId == termId &&
          other.learnerCount == learnerCount &&
          other.createdAt == createdAt &&
          other.releasedAt == releasedAt;

  @override
  int get hashCode =>
      id.hashCode +
      state.hashCode +
      mode.hashCode +
      classSectionId.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      learnerCount.hashCode +
      createdAt.hashCode +
      (releasedAt == null ? 0 : releasedAt.hashCode);

  factory AcademicReportBatchSummary.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportBatchSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportBatchSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
