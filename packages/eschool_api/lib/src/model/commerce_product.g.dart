// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_product.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceProduct _$CommerceProductFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CommerceProduct',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'name',
            'description',
            'sku',
            'unit_label',
            'category_key',
            'currency',
            'price_cents',
            'vat_kind',
            'vat_rate_bps',
            'in_stock',
          ],
        );
        final val = CommerceProduct(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          description: $checkedConvert('description', (v) => v as String?),
          sku: $checkedConvert('sku', (v) => v as String),
          unitLabel: $checkedConvert('unit_label', (v) => v as String),
          categoryKey: $checkedConvert('category_key', (v) => v as String),
          currency: $checkedConvert('currency', (v) => v as String),
          priceCents: $checkedConvert('price_cents', (v) => (v as num).toInt()),
          vatKind: $checkedConvert(
            'vat_kind',
            (v) => $enumDecode(
              _$CommerceProductVatKindEnumEnumMap,
              v,
              unknownValue: CommerceProductVatKindEnum.unknownDefaultOpenApi,
            ),
          ),
          vatRateBps: $checkedConvert(
            'vat_rate_bps',
            (v) => (v as num).toInt(),
          ),
          inStock: $checkedConvert('in_stock', (v) => v as bool),
          ticket: $checkedConvert(
            'ticket',
            (v) => v == null
                ? null
                : AdmissionProduct.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'unitLabel': 'unit_label',
        'categoryKey': 'category_key',
        'priceCents': 'price_cents',
        'vatKind': 'vat_kind',
        'vatRateBps': 'vat_rate_bps',
        'inStock': 'in_stock',
      },
    );

Map<String, dynamic> _$CommerceProductToJson(CommerceProduct instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'description': instance.description,
      'sku': instance.sku,
      'unit_label': instance.unitLabel,
      'category_key': instance.categoryKey,
      'currency': instance.currency,
      'price_cents': instance.priceCents,
      'vat_kind': _$CommerceProductVatKindEnumEnumMap[instance.vatKind]!,
      'vat_rate_bps': instance.vatRateBps,
      'in_stock': instance.inStock,
      'ticket': ?instance.ticket?.toJson(),
    };

const _$CommerceProductVatKindEnumEnumMap = {
  CommerceProductVatKindEnum.none: 'none',
  CommerceProductVatKindEnum.zero: 'zero',
  CommerceProductVatKindEnum.exempt: 'exempt',
  CommerceProductVatKindEnum.standard: 'standard',
  CommerceProductVatKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
