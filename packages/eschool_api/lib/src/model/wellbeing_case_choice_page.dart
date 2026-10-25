//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wellbeing_named_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_choice_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCaseChoicePage {
  /// Returns a new [WellbeingCaseChoicePage] instance.
  WellbeingCaseChoicePage({
    required this.items,

    required this.page,

    required this.lastPage,

    this.selectedAvailable,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<WellbeingNamedChoice> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'selected_available', required: false, includeIfNull: false)
  final bool? selectedAvailable;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCaseChoicePage &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.selectedAvailable == selectedAvailable;

  @override
  int get hashCode =>
      items.hashCode +
      page.hashCode +
      lastPage.hashCode +
      selectedAvailable.hashCode;

  factory WellbeingCaseChoicePage.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseChoicePageFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseChoicePageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
