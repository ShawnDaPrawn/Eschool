//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_blueprint_list_item.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_blueprint_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestBlueprintPage {
  /// Returns a new [PracticeTestBlueprintPage] instance.
  PracticeTestBlueprintPage({
    required this.groupId,

    required this.tests,

    required this.page,

    required this.hasMore,
  });

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'tests', required: true, includeIfNull: false)
  final List<PracticeTestBlueprintListItem> tests;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'has_more', required: true, includeIfNull: false)
  final bool hasMore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestBlueprintPage &&
          other.groupId == groupId &&
          other.tests == tests &&
          other.page == page &&
          other.hasMore == hasMore;

  @override
  int get hashCode =>
      groupId.hashCode + tests.hashCode + page.hashCode + hasMore.hashCode;

  factory PracticeTestBlueprintPage.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestBlueprintPageFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestBlueprintPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
