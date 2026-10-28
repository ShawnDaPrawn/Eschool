//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'vendor_settlement_line.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorSettlementLine {
  /// Returns a new [VendorSettlementLine] instance.
  VendorSettlementLine({
    required this.orderNumber,

    required this.grossCents,

    required this.salesNetCents,

    required this.vatCents,
  });

  @JsonKey(name: r'order_number', required: true, includeIfNull: false)
  final String orderNumber;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'gross_cents', required: true, includeIfNull: false)
  final int grossCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'sales_net_cents', required: true, includeIfNull: false)
  final int salesNetCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'vat_cents', required: true, includeIfNull: false)
  final int vatCents;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VendorSettlementLine &&
          other.orderNumber == orderNumber &&
          other.grossCents == grossCents &&
          other.salesNetCents == salesNetCents &&
          other.vatCents == vatCents;

  @override
  int get hashCode =>
      orderNumber.hashCode +
      grossCents.hashCode +
      salesNetCents.hashCode +
      vatCents.hashCode;

  factory VendorSettlementLine.fromJson(Map<String, dynamic> json) =>
      _$VendorSettlementLineFromJson(json);

  Map<String, dynamic> toJson() => _$VendorSettlementLineToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
