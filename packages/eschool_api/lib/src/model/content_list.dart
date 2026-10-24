//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/content_list_item.dart';
import 'package:json_annotation/json_annotation.dart';

part 'content_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentList {
  /// Returns a new [ContentList] instance.
  ContentList({
    required this.page,

    required this.lastPage,

    required this.items,
  });

  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ContentListItem> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentList &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.items == items;

  @override
  int get hashCode => page.hashCode + lastPage.hashCode + items.hashCode;

  factory ContentList.fromJson(Map<String, dynamic> json) =>
      _$ContentListFromJson(json);

  Map<String, dynamic> toJson() => _$ContentListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
