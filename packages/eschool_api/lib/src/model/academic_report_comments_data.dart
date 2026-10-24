//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_comment_row.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_comments_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportCommentsData {
  /// Returns a new [AcademicReportCommentsData] instance.
  AcademicReportCommentsData({
    required this.sessionYearId,

    required this.page,

    required this.lastPage,

    required this.rows,
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

  @JsonKey(name: r'rows', required: true, includeIfNull: false)
  final List<AcademicReportCommentRow> rows;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportCommentsData &&
          other.sessionYearId == sessionYearId &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.rows == rows;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      page.hashCode +
      lastPage.hashCode +
      rows.hashCode;

  factory AcademicReportCommentsData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportCommentsDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportCommentsDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
