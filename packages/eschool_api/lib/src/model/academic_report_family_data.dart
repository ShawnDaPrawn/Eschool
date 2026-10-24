//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_family_card.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_family_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportFamilyData {
  /// Returns a new [AcademicReportFamilyData] instance.
  AcademicReportFamilyData({
    required this.learnerId,

    required this.page,

    required this.lastPage,

    required this.reports,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'reports', required: true, includeIfNull: false)
  final List<AcademicReportFamilyCard> reports;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportFamilyData &&
          other.learnerId == learnerId &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.reports == reports;

  @override
  int get hashCode =>
      learnerId.hashCode + page.hashCode + lastPage.hashCode + reports.hashCode;

  factory AcademicReportFamilyData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportFamilyDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportFamilyDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
