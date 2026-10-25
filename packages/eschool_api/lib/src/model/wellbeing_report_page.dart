//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wellbeing_report_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_report_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingReportPage {
  /// Returns a new [WellbeingReportPage] instance.
  WellbeingReportPage({
    required this.items,

    required this.page,

    required this.lastPage,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<WellbeingReportSummary> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingReportPage &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode => items.hashCode + page.hashCode + lastPage.hashCode;

  factory WellbeingReportPage.fromJson(Map<String, dynamic> json) =>
      _$WellbeingReportPageFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingReportPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
