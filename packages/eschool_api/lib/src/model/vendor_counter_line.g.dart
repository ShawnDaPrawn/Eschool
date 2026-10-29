// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_counter_line.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorCounterLine _$VendorCounterLineFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'VendorCounterLine',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'product_name',
            'sku',
            'unit_label',
            'quantity',
            'gross_cents',
            'net_cents',
            'vat_cents',
          ],
        );
        final val = VendorCounterLine(
          productName: $checkedConvert('product_name', (v) => v as String),
          sku: $checkedConvert('sku', (v) => v as String),
          unitLabel: $checkedConvert('unit_label', (v) => v as String),
          quantity: $checkedConvert('quantity', (v) => (v as num).toInt()),
          grossCents: $checkedConvert('gross_cents', (v) => (v as num).toInt()),
          netCents: $checkedConvert('net_cents', (v) => (v as num).toInt()),
          vatCents: $checkedConvert('vat_cents', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'productName': 'product_name',
        'unitLabel': 'unit_label',
        'grossCents': 'gross_cents',
        'netCents': 'net_cents',
        'vatCents': 'vat_cents',
      },
    );

Map<String, dynamic> _$VendorCounterLineToJson(VendorCounterLine instance) =>
    <String, dynamic>{
      'product_name': instance.productName,
      'sku': instance.sku,
      'unit_label': instance.unitLabel,
      'quantity': instance.quantity,
      'gross_cents': instance.grossCents,
      'net_cents': instance.netCents,
      'vat_cents': instance.vatCents,
    };
