//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/parent_fee_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'parent_fee_receipt_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ParentFeeReceiptPage {
  /// Returns a new [ParentFeeReceiptPage] instance.
  ParentFeeReceiptPage({
    required this.items,

    required this.currentPage,

    required this.perPage,

    required this.total,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ParentFeeReceipt> items;

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
      other is ParentFeeReceiptPage &&
          other.items == items &&
          other.currentPage == currentPage &&
          other.perPage == perPage &&
          other.total == total;

  @override
  int get hashCode =>
      items.hashCode + currentPage.hashCode + perPage.hashCode + total.hashCode;

  factory ParentFeeReceiptPage.fromJson(Map<String, dynamic> json) =>
      _$ParentFeeReceiptPageFromJson(json);

  Map<String, dynamic> toJson() => _$ParentFeeReceiptPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
