//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/teaching_group.dart';
import 'package:json_annotation/json_annotation.dart';

part 'teaching_groups_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingGroupsResponseData {
  /// Returns a new [TeachingGroupsResponseData] instance.
  TeachingGroupsResponseData({
    required this.groups,

    required this.currentPage,

    required this.lastPage,

    required this.total,
  });

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<TeachingGroup> groups;

  // minimum: 1
  @JsonKey(name: r'current_page', required: true, includeIfNull: false)
  final int currentPage;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingGroupsResponseData &&
          other.groups == groups &&
          other.currentPage == currentPage &&
          other.lastPage == lastPage &&
          other.total == total;

  @override
  int get hashCode =>
      groups.hashCode +
      currentPage.hashCode +
      lastPage.hashCode +
      total.hashCode;

  factory TeachingGroupsResponseData.fromJson(Map<String, dynamic> json) =>
      _$TeachingGroupsResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingGroupsResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
