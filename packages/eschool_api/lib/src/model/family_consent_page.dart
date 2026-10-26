//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_consent_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentPage {
  /// Returns a new [FamilyConsentPage] instance.
  FamilyConsentPage({required this.items, required this.nextPage});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<FamilyConsentSummary> items;

  // minimum: 1
  @JsonKey(name: r'next_page', required: true, includeIfNull: true)
  final int? nextPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentPage &&
          other.items == items &&
          other.nextPage == nextPage;

  @override
  int get hashCode =>
      items.hashCode + (nextPage == null ? 0 : nextPage.hashCode);

  factory FamilyConsentPage.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentPageFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
