//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'commerce_receipt_line.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceReceiptLine {
  /// Returns a new [CommerceReceiptLine] instance.
  CommerceReceiptLine({
    required this.productId,

    required this.name,

    required this.sku,

    required this.unitLabel,

    required this.categoryKey,

    required this.quantity,

    required this.unitPriceCents,

    required this.grossCents,

    required this.netCents,

    required this.vatCents,

    required this.vatKind,

    required this.vatRateBps,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'product_id', required: true, includeIfNull: false)
  final int productId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'sku', required: true, includeIfNull: false)
  final String sku;

  @JsonKey(name: r'unit_label', required: true, includeIfNull: false)
  final String unitLabel;

  @JsonKey(name: r'category_key', required: true, includeIfNull: false)
  final String categoryKey;

  // minimum: 1
  // maximum: 999
  @JsonKey(name: r'quantity', required: true, includeIfNull: false)
  final int quantity;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'unit_price_cents', required: true, includeIfNull: false)
  final int unitPriceCents;

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

  @JsonKey(
    name: r'vat_kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CommerceReceiptLineVatKindEnum.unknownDefaultOpenApi,
  )
  final CommerceReceiptLineVatKindEnum vatKind;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'vat_rate_bps', required: true, includeIfNull: false)
  final int vatRateBps;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceReceiptLine &&
          other.productId == productId &&
          other.name == name &&
          other.sku == sku &&
          other.unitLabel == unitLabel &&
          other.categoryKey == categoryKey &&
          other.quantity == quantity &&
          other.unitPriceCents == unitPriceCents &&
          other.grossCents == grossCents &&
          other.netCents == netCents &&
          other.vatCents == vatCents &&
          other.vatKind == vatKind &&
          other.vatRateBps == vatRateBps;

  @override
  int get hashCode =>
      productId.hashCode +
      name.hashCode +
      sku.hashCode +
      unitLabel.hashCode +
      categoryKey.hashCode +
      quantity.hashCode +
      unitPriceCents.hashCode +
      grossCents.hashCode +
      netCents.hashCode +
      vatCents.hashCode +
      vatKind.hashCode +
      vatRateBps.hashCode;

  factory CommerceReceiptLine.fromJson(Map<String, dynamic> json) =>
      _$CommerceReceiptLineFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceReceiptLineToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommerceReceiptLineVatKindEnum {
  @JsonValue(r'none')
  none(r'none'),
  @JsonValue(r'zero')
  zero(r'zero'),
  @JsonValue(r'exempt')
  exempt(r'exempt'),
  @JsonValue(r'standard')
  standard(r'standard'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommerceReceiptLineVatKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
