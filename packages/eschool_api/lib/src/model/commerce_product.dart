//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/admission_product.dart';
import 'package:json_annotation/json_annotation.dart';

part 'commerce_product.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceProduct {
  /// Returns a new [CommerceProduct] instance.
  CommerceProduct({
    required this.id,

    required this.name,

    required this.description,

    required this.sku,

    required this.unitLabel,

    required this.categoryKey,

    required this.currency,

    required this.priceCents,

    required this.vatKind,

    required this.vatRateBps,

    required this.inStock,

    this.ticket,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'description', required: true, includeIfNull: true)
  final String? description;

  @JsonKey(name: r'sku', required: true, includeIfNull: false)
  final String sku;

  @JsonKey(name: r'unit_label', required: true, includeIfNull: false)
  final String unitLabel;

  @JsonKey(name: r'category_key', required: true, includeIfNull: false)
  final String categoryKey;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'price_cents', required: true, includeIfNull: false)
  final int priceCents;

  @JsonKey(
    name: r'vat_kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CommerceProductVatKindEnum.unknownDefaultOpenApi,
  )
  final CommerceProductVatKindEnum vatKind;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'vat_rate_bps', required: true, includeIfNull: false)
  final int vatRateBps;

  @JsonKey(name: r'in_stock', required: true, includeIfNull: false)
  final bool inStock;

  @JsonKey(name: r'ticket', required: false, includeIfNull: false)
  final AdmissionProduct? ticket;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceProduct &&
          other.id == id &&
          other.name == name &&
          other.description == description &&
          other.sku == sku &&
          other.unitLabel == unitLabel &&
          other.categoryKey == categoryKey &&
          other.currency == currency &&
          other.priceCents == priceCents &&
          other.vatKind == vatKind &&
          other.vatRateBps == vatRateBps &&
          other.inStock == inStock &&
          other.ticket == ticket;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (description == null ? 0 : description.hashCode) +
      sku.hashCode +
      unitLabel.hashCode +
      categoryKey.hashCode +
      currency.hashCode +
      priceCents.hashCode +
      vatKind.hashCode +
      vatRateBps.hashCode +
      inStock.hashCode +
      ticket.hashCode;

  factory CommerceProduct.fromJson(Map<String, dynamic> json) =>
      _$CommerceProductFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceProductToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommerceProductVatKindEnum {
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

  const CommerceProductVatKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
