//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_audience_choice_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationAudienceChoicePage {
  /// Returns a new [ConsultationAudienceChoicePage] instance.
  ConsultationAudienceChoicePage({
    required this.items,

    required this.page,

    required this.lastPage,

    required this.selectedId,

    required this.selectedName,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ConsultationChoice> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 1
  @JsonKey(name: r'selected_id', required: true, includeIfNull: true)
  final int? selectedId;

  @JsonKey(name: r'selected_name', required: true, includeIfNull: true)
  final String? selectedName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationAudienceChoicePage &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.selectedId == selectedId &&
          other.selectedName == selectedName;

  @override
  int get hashCode =>
      items.hashCode +
      page.hashCode +
      lastPage.hashCode +
      (selectedId == null ? 0 : selectedId.hashCode) +
      (selectedName == null ? 0 : selectedName.hashCode);

  factory ConsultationAudienceChoicePage.fromJson(Map<String, dynamic> json) =>
      _$ConsultationAudienceChoicePageFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationAudienceChoicePageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
