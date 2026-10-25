//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/secure_card.dart';
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardPage {
  /// Returns a new [SecureCardPage] instance.
  SecureCardPage({required this.items, required this.nextPage});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<SecureCard> items;

  // minimum: 1
  @JsonKey(name: r'next_page', required: true, includeIfNull: true)
  final int? nextPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardPage &&
          other.items == items &&
          other.nextPage == nextPage;

  @override
  int get hashCode =>
      items.hashCode + (nextPage == null ? 0 : nextPage.hashCode);

  factory SecureCardPage.fromJson(Map<String, dynamic> json) =>
      _$SecureCardPageFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
