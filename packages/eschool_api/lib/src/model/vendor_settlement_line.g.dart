// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_settlement_line.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorSettlementLine _$VendorSettlementLineFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'VendorSettlementLine',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'order_number',
        'gross_cents',
        'sales_net_cents',
        'vat_cents',
      ],
    );
    final val = VendorSettlementLine(
      orderNumber: $checkedConvert('order_number', (v) => v as String),
      grossCents: $checkedConvert('gross_cents', (v) => (v as num).toInt()),
      salesNetCents: $checkedConvert(
        'sales_net_cents',
        (v) => (v as num).toInt(),
      ),
      vatCents: $checkedConvert('vat_cents', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'orderNumber': 'order_number',
    'grossCents': 'gross_cents',
    'salesNetCents': 'sales_net_cents',
    'vatCents': 'vat_cents',
  },
);

Map<String, dynamic> _$VendorSettlementLineToJson(
  VendorSettlementLine instance,
) => <String, dynamic>{
  'order_number': instance.orderNumber,
  'gross_cents': instance.grossCents,
  'sales_net_cents': instance.salesNetCents,
  'vat_cents': instance.vatCents,
};
