//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/activity_group.dart';
import 'package:json_annotation/json_annotation.dart';

part 'activity_group_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityGroupList {
  /// Returns a new [ActivityGroupList] instance.
  ActivityGroupList({
    required this.items,

    required this.page,

    required this.lastPage,

    required this.total,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ActivityGroup> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityGroupList &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.total == total;

  @override
  int get hashCode =>
      items.hashCode + page.hashCode + lastPage.hashCode + total.hashCode;

  factory ActivityGroupList.fromJson(Map<String, dynamic> json) =>
      _$ActivityGroupListFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityGroupListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
