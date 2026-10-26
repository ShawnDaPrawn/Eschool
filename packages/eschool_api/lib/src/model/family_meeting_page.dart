//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_meeting_slot.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_meeting_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyMeetingPage {
  /// Returns a new [FamilyMeetingPage] instance.
  FamilyMeetingPage({required this.items, required this.nextPage});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<FamilyMeetingSlot> items;

  // minimum: 1
  @JsonKey(name: r'next_page', required: true, includeIfNull: true)
  final int? nextPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyMeetingPage &&
          other.items == items &&
          other.nextPage == nextPage;

  @override
  int get hashCode =>
      items.hashCode + (nextPage == null ? 0 : nextPage.hashCode);

  factory FamilyMeetingPage.fromJson(Map<String, dynamic> json) =>
      _$FamilyMeetingPageFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyMeetingPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
