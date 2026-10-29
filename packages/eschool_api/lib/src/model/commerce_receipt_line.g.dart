// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_receipt_line.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceReceiptLine _$CommerceReceiptLineFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommerceReceiptLine',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'product_id',
        'name',
        'sku',
        'unit_label',
        'category_key',
        'quantity',
        'unit_price_cents',
        'gross_cents',
        'net_cents',
        'vat_cents',
        'vat_kind',
        'vat_rate_bps',
      ],
    );
    final val = CommerceReceiptLine(
      productId: $checkedConvert('product_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      sku: $checkedConvert('sku', (v) => v as String),
      unitLabel: $checkedConvert('unit_label', (v) => v as String),
      categoryKey: $checkedConvert('category_key', (v) => v as String),
      quantity: $checkedConvert('quantity', (v) => (v as num).toInt()),
      unitPriceCents: $checkedConvert(
        'unit_price_cents',
        (v) => (v as num).toInt(),
      ),
      grossCents: $checkedConvert('gross_cents', (v) => (v as num).toInt()),
      netCents: $checkedConvert('net_cents', (v) => (v as num).toInt()),
      vatCents: $checkedConvert('vat_cents', (v) => (v as num).toInt()),
      vatKind: $checkedConvert(
        'vat_kind',
        (v) => $enumDecode(
          _$CommerceReceiptLineVatKindEnumEnumMap,
          v,
          unknownValue: CommerceReceiptLineVatKindEnum.unknownDefaultOpenApi,
        ),
      ),
      vatRateBps: $checkedConvert('vat_rate_bps', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'productId': 'product_id',
    'unitLabel': 'unit_label',
    'categoryKey': 'category_key',
    'unitPriceCents': 'unit_price_cents',
    'grossCents': 'gross_cents',
    'netCents': 'net_cents',
    'vatCents': 'vat_cents',
    'vatKind': 'vat_kind',
    'vatRateBps': 'vat_rate_bps',
  },
);

Map<String, dynamic> _$CommerceReceiptLineToJson(
  CommerceReceiptLine instance,
) => <String, dynamic>{
  'product_id': instance.productId,
  'name': instance.name,
  'sku': instance.sku,
  'unit_label': instance.unitLabel,
  'category_key': instance.categoryKey,
  'quantity': instance.quantity,
  'unit_price_cents': instance.unitPriceCents,
  'gross_cents': instance.grossCents,
  'net_cents': instance.netCents,
  'vat_cents': instance.vatCents,
  'vat_kind': _$CommerceReceiptLineVatKindEnumEnumMap[instance.vatKind]!,
  'vat_rate_bps': instance.vatRateBps,
};

const _$CommerceReceiptLineVatKindEnumEnumMap = {
  CommerceReceiptLineVatKindEnum.none: 'none',
  CommerceReceiptLineVatKindEnum.zero: 'zero',
  CommerceReceiptLineVatKindEnum.exempt: 'exempt',
  CommerceReceiptLineVatKindEnum.standard: 'standard',
  CommerceReceiptLineVatKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
