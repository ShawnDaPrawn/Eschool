//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wellbeing_assignment.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_assignment_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingAssignmentPage {
  /// Returns a new [WellbeingAssignmentPage] instance.
  WellbeingAssignmentPage({
    required this.items,

    required this.page,

    required this.lastPage,

    required this.ownerId,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<WellbeingAssignment> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 1
  @JsonKey(name: r'owner_id', required: true, includeIfNull: false)
  final int ownerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingAssignmentPage &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.ownerId == ownerId;

  @override
  int get hashCode =>
      items.hashCode + page.hashCode + lastPage.hashCode + ownerId.hashCode;

  factory WellbeingAssignmentPage.fromJson(Map<String, dynamic> json) =>
      _$WellbeingAssignmentPageFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingAssignmentPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
