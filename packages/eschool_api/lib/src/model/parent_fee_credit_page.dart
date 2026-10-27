//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/parent_fee_credit_note.dart';
import 'package:json_annotation/json_annotation.dart';

part 'parent_fee_credit_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ParentFeeCreditPage {
  /// Returns a new [ParentFeeCreditPage] instance.
  ParentFeeCreditPage({
    required this.items,

    required this.currentPage,

    required this.perPage,

    required this.total,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ParentFeeCreditNote> items;

  // minimum: 1
  @JsonKey(name: r'current_page', required: true, includeIfNull: false)
  final int currentPage;

  // minimum: 1
  // maximum: 50
  @JsonKey(name: r'per_page', required: true, includeIfNull: false)
  final int perPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ParentFeeCreditPage &&
          other.items == items &&
          other.currentPage == currentPage &&
          other.perPage == perPage &&
          other.total == total;

  @override
  int get hashCode =>
      items.hashCode + currentPage.hashCode + perPage.hashCode + total.hashCode;

  factory ParentFeeCreditPage.fromJson(Map<String, dynamic> json) =>
      _$ParentFeeCreditPageFromJson(json);

  Map<String, dynamic> toJson() => _$ParentFeeCreditPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
