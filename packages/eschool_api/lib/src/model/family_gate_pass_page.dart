//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_gate_pass.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_gate_pass_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyGatePassPage {
  /// Returns a new [FamilyGatePassPage] instance.
  FamilyGatePassPage({required this.items, required this.nextPage});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<FamilyGatePass> items;

  // minimum: 1
  @JsonKey(name: r'next_page', required: true, includeIfNull: true)
  final int? nextPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyGatePassPage &&
          other.items == items &&
          other.nextPage == nextPage;

  @override
  int get hashCode =>
      items.hashCode + (nextPage == null ? 0 : nextPage.hashCode);

  factory FamilyGatePassPage.fromJson(Map<String, dynamic> json) =>
      _$FamilyGatePassPageFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyGatePassPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
