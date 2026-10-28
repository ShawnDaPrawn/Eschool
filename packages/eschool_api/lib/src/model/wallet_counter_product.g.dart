// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_counter_product.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCounterProduct _$WalletCounterProductFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletCounterProduct',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'name',
        'unit_label',
        'revision',
        'unit_price_cents',
        'vat_kind',
        'vat_rate_bps',
        'available_quantity',
      ],
    );
    final val = WalletCounterProduct(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      unitLabel: $checkedConvert('unit_label', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      unitPriceCents: $checkedConvert(
        'unit_price_cents',
        (v) => (v as num).toInt(),
      ),
      vatKind: $checkedConvert(
        'vat_kind',
        (v) => $enumDecode(
          _$WalletCounterProductVatKindEnumEnumMap,
          v,
          unknownValue: WalletCounterProductVatKindEnum.unknownDefaultOpenApi,
        ),
      ),
      vatRateBps: $checkedConvert('vat_rate_bps', (v) => (v as num).toInt()),
      availableQuantity: $checkedConvert(
        'available_quantity',
        (v) => (v as num?)?.toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'unitLabel': 'unit_label',
    'unitPriceCents': 'unit_price_cents',
    'vatKind': 'vat_kind',
    'vatRateBps': 'vat_rate_bps',
    'availableQuantity': 'available_quantity',
  },
);

Map<String, dynamic> _$WalletCounterProductToJson(
  WalletCounterProduct instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'unit_label': instance.unitLabel,
  'revision': instance.revision,
  'unit_price_cents': instance.unitPriceCents,
  'vat_kind': _$WalletCounterProductVatKindEnumEnumMap[instance.vatKind]!,
  'vat_rate_bps': instance.vatRateBps,
  'available_quantity': instance.availableQuantity,
};

const _$WalletCounterProductVatKindEnumEnumMap = {
  WalletCounterProductVatKindEnum.none: 'none',
  WalletCounterProductVatKindEnum.exempt: 'exempt',
  WalletCounterProductVatKindEnum.zero: 'zero',
  WalletCounterProductVatKindEnum.standard: 'standard',
  WalletCounterProductVatKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
