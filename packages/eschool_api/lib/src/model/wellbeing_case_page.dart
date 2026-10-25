//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wellbeing_case_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCasePage {
  /// Returns a new [WellbeingCasePage] instance.
  WellbeingCasePage({
    required this.items,

    required this.page,

    required this.lastPage,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<WellbeingCaseSummary> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCasePage &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode => items.hashCode + page.hashCode + lastPage.hashCode;

  factory WellbeingCasePage.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCasePageFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCasePageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
