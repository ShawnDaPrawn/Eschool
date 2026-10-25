//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/support_assignment.dart';
import 'package:json_annotation/json_annotation.dart';

part 'support_assignments.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportAssignments {
  /// Returns a new [SupportAssignments] instance.
  SupportAssignments({
    required this.page,

    required this.lastPage,

    required this.items,
  });

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<SupportAssignment> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportAssignments &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.items == items;

  @override
  int get hashCode => page.hashCode + lastPage.hashCode + items.hashCode;

  factory SupportAssignments.fromJson(Map<String, dynamic> json) =>
      _$SupportAssignmentsFromJson(json);

  Map<String, dynamic> toJson() => _$SupportAssignmentsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
