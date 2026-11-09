//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/fixture_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'fixture_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureList {
  /// Returns a new [FixtureList] instance.
  FixtureList({
    required this.items,

    required this.page,

    required this.lastPage,

    required this.total,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<FixtureSummary> items;

  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureList &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.total == total;

  @override
  int get hashCode =>
      items.hashCode + page.hashCode + lastPage.hashCode + total.hashCode;

  factory FixtureList.fromJson(Map<String, dynamic> json) =>
      _$FixtureListFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
