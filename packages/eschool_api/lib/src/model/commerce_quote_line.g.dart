// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_quote_line.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceQuoteLine _$CommerceQuoteLineFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CommerceQuoteLine',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'product_id',
            'product_revision',
            'product_name',
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
            'tracks_stock',
          ],
        );
        final val = CommerceQuoteLine(
          productId: $checkedConvert('product_id', (v) => (v as num).toInt()),
          productRevision: $checkedConvert(
            'product_revision',
            (v) => (v as num).toInt(),
          ),
          productName: $checkedConvert('product_name', (v) => v as String),
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
              _$CommerceQuoteLineVatKindEnumEnumMap,
              v,
              unknownValue: CommerceQuoteLineVatKindEnum.unknownDefaultOpenApi,
            ),
          ),
          vatRateBps: $checkedConvert(
            'vat_rate_bps',
            (v) => (v as num).toInt(),
          ),
          tracksStock: $checkedConvert('tracks_stock', (v) => v as bool),
          ticket: $checkedConvert(
            'ticket',
            (v) => v == null
                ? null
                : AdmissionQuoteDetails.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'productId': 'product_id',
        'productRevision': 'product_revision',
        'productName': 'product_name',
        'unitLabel': 'unit_label',
        'categoryKey': 'category_key',
        'unitPriceCents': 'unit_price_cents',
        'grossCents': 'gross_cents',
        'netCents': 'net_cents',
        'vatCents': 'vat_cents',
        'vatKind': 'vat_kind',
        'vatRateBps': 'vat_rate_bps',
        'tracksStock': 'tracks_stock',
      },
    );

Map<String, dynamic> _$CommerceQuoteLineToJson(CommerceQuoteLine instance) =>
    <String, dynamic>{
      'product_id': instance.productId,
      'product_revision': instance.productRevision,
      'product_name': instance.productName,
      'sku': instance.sku,
      'unit_label': instance.unitLabel,
      'category_key': instance.categoryKey,
      'quantity': instance.quantity,
      'unit_price_cents': instance.unitPriceCents,
      'gross_cents': instance.grossCents,
      'net_cents': instance.netCents,
      'vat_cents': instance.vatCents,
      'vat_kind': _$CommerceQuoteLineVatKindEnumEnumMap[instance.vatKind]!,
      'vat_rate_bps': instance.vatRateBps,
      'tracks_stock': instance.tracksStock,
      'ticket': ?instance.ticket?.toJson(),
    };

const _$CommerceQuoteLineVatKindEnumEnumMap = {
  CommerceQuoteLineVatKindEnum.none: 'none',
  CommerceQuoteLineVatKindEnum.zero: 'zero',
  CommerceQuoteLineVatKindEnum.exempt: 'exempt',
  CommerceQuoteLineVatKindEnum.standard: 'standard',
  CommerceQuoteLineVatKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
