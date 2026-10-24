//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_batch_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_batches_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportBatchesData {
  /// Returns a new [AcademicReportBatchesData] instance.
  AcademicReportBatchesData({
    required this.sessionYearId,

    required this.page,

    required this.lastPage,

    required this.batches,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'batches', required: true, includeIfNull: false)
  final List<AcademicReportBatchSummary> batches;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportBatchesData &&
          other.sessionYearId == sessionYearId &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.batches == batches;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      page.hashCode +
      lastPage.hashCode +
      batches.hashCode;

  factory AcademicReportBatchesData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportBatchesDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportBatchesDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
