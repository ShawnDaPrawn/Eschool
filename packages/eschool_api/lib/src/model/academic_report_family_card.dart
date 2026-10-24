//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_family_card.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportFamilyCard {
  /// Returns a new [AcademicReportFamilyCard] instance.
  AcademicReportFamilyCard({
    required this.id,

    required this.batchId,

    required this.sessionYearId,

    required this.termId,

    required this.yearName,

    required this.termName,

    required this.learnerName,

    required this.provisional,

    required this.releasedAt,

    required this.readAt,

    required this.downloadUrl,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'batch_id', required: true, includeIfNull: false)
  final int batchId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: true)
  final int? termId;

  @JsonKey(name: r'year_name', required: true, includeIfNull: false)
  final String yearName;

  @JsonKey(name: r'term_name', required: true, includeIfNull: true)
  final String? termName;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(name: r'provisional', required: true, includeIfNull: false)
  final bool provisional;

  @JsonKey(name: r'released_at', required: true, includeIfNull: false)
  final String releasedAt;

  @JsonKey(name: r'read_at', required: true, includeIfNull: true)
  final String? readAt;

  @JsonKey(name: r'download_url', required: true, includeIfNull: false)
  final String downloadUrl;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportFamilyCard &&
          other.id == id &&
          other.batchId == batchId &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.yearName == yearName &&
          other.termName == termName &&
          other.learnerName == learnerName &&
          other.provisional == provisional &&
          other.releasedAt == releasedAt &&
          other.readAt == readAt &&
          other.downloadUrl == downloadUrl;

  @override
  int get hashCode =>
      id.hashCode +
      batchId.hashCode +
      sessionYearId.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      yearName.hashCode +
      (termName == null ? 0 : termName.hashCode) +
      learnerName.hashCode +
      provisional.hashCode +
      releasedAt.hashCode +
      (readAt == null ? 0 : readAt.hashCode) +
      downloadUrl.hashCode;

  factory AcademicReportFamilyCard.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportFamilyCardFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportFamilyCardToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
