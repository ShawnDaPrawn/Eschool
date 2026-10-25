//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/weekly_digest_list_item.dart';
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestPage {
  /// Returns a new [WeeklyDigestPage] instance.
  WeeklyDigestPage({
    required this.items,

    required this.total,

    required this.nextBefore,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<WeeklyDigestListItem> items;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  // minimum: 1
  @JsonKey(name: r'next_before', required: true, includeIfNull: true)
  final int? nextBefore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestPage &&
          other.items == items &&
          other.total == total &&
          other.nextBefore == nextBefore;

  @override
  int get hashCode =>
      items.hashCode +
      total.hashCode +
      (nextBefore == null ? 0 : nextBefore.hashCode);

  factory WeeklyDigestPage.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestPageFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
