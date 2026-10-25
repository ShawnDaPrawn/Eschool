//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/secure_card_person.dart';
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_people_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardPeoplePage {
  /// Returns a new [SecureCardPeoplePage] instance.
  SecureCardPeoplePage({required this.items, required this.nextPage});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<SecureCardPerson> items;

  // minimum: 1
  @JsonKey(name: r'next_page', required: true, includeIfNull: true)
  final int? nextPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardPeoplePage &&
          other.items == items &&
          other.nextPage == nextPage;

  @override
  int get hashCode =>
      items.hashCode + (nextPage == null ? 0 : nextPage.hashCode);

  factory SecureCardPeoplePage.fromJson(Map<String, dynamic> json) =>
      _$SecureCardPeoplePageFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardPeoplePageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
