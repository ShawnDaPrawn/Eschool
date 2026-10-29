//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'vendor_counter_line.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorCounterLine {
  /// Returns a new [VendorCounterLine] instance.
  VendorCounterLine({
    required this.productName,

    required this.sku,

    required this.unitLabel,

    required this.quantity,

    required this.grossCents,

    required this.netCents,

    required this.vatCents,
  });

  @JsonKey(name: r'product_name', required: true, includeIfNull: false)
  final String productName;

  @JsonKey(name: r'sku', required: true, includeIfNull: false)
  final String sku;

  @JsonKey(name: r'unit_label', required: true, includeIfNull: false)
  final String unitLabel;

  // minimum: 1
  // maximum: 999
  @JsonKey(name: r'quantity', required: true, includeIfNull: false)
  final int quantity;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'gross_cents', required: true, includeIfNull: false)
  final int grossCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'net_cents', required: true, includeIfNull: false)
  final int netCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'vat_cents', required: true, includeIfNull: false)
  final int vatCents;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VendorCounterLine &&
          other.productName == productName &&
          other.sku == sku &&
          other.unitLabel == unitLabel &&
          other.quantity == quantity &&
          other.grossCents == grossCents &&
          other.netCents == netCents &&
          other.vatCents == vatCents;

  @override
  int get hashCode =>
      productName.hashCode +
      sku.hashCode +
      unitLabel.hashCode +
      quantity.hashCode +
      grossCents.hashCode +
      netCents.hashCode +
      vatCents.hashCode;

  factory VendorCounterLine.fromJson(Map<String, dynamic> json) =>
      _$VendorCounterLineFromJson(json);

  Map<String, dynamic> toJson() => _$VendorCounterLineToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
