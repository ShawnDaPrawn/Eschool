//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_counter_product.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletCounterProduct {
  /// Returns a new [WalletCounterProduct] instance.
  WalletCounterProduct({
    required this.id,

    required this.name,

    required this.unitLabel,

    required this.revision,

    required this.unitPriceCents,

    required this.vatKind,

    required this.vatRateBps,

    required this.availableQuantity,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'unit_label', required: true, includeIfNull: false)
  final String unitLabel;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'unit_price_cents', required: true, includeIfNull: false)
  final int unitPriceCents;

  @JsonKey(
    name: r'vat_kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletCounterProductVatKindEnum.unknownDefaultOpenApi,
  )
  final WalletCounterProductVatKindEnum vatKind;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'vat_rate_bps', required: true, includeIfNull: false)
  final int vatRateBps;

  // minimum: 0
  @JsonKey(name: r'available_quantity', required: true, includeIfNull: true)
  final int? availableQuantity;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletCounterProduct &&
          other.id == id &&
          other.name == name &&
          other.unitLabel == unitLabel &&
          other.revision == revision &&
          other.unitPriceCents == unitPriceCents &&
          other.vatKind == vatKind &&
          other.vatRateBps == vatRateBps &&
          other.availableQuantity == availableQuantity;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      unitLabel.hashCode +
      revision.hashCode +
      unitPriceCents.hashCode +
      vatKind.hashCode +
      vatRateBps.hashCode +
      (availableQuantity == null ? 0 : availableQuantity.hashCode);

  factory WalletCounterProduct.fromJson(Map<String, dynamic> json) =>
      _$WalletCounterProductFromJson(json);

  Map<String, dynamic> toJson() => _$WalletCounterProductToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletCounterProductVatKindEnum {
  @JsonValue(r'none')
  none(r'none'),
  @JsonValue(r'exempt')
  exempt(r'exempt'),
  @JsonValue(r'zero')
  zero(r'zero'),
  @JsonValue(r'standard')
  standard(r'standard'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletCounterProductVatKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
