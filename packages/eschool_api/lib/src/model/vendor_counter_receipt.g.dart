// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_counter_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorCounterReceipt _$VendorCounterReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'VendorCounterReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'vendor_id',
        'id',
        'order_number',
        'status',
        'currency',
        'amount_cents',
        'net_cents',
        'vat_cents',
        'created_at',
        'processed_at',
        'can_collect',
        'lines',
      ],
    );
    final val = VendorCounterReceipt(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      orderNumber: $checkedConvert('order_number', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$VendorCounterReceiptStatusEnumEnumMap,
          v,
          unknownValue: VendorCounterReceiptStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      currency: $checkedConvert('currency', (v) => v as String),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      netCents: $checkedConvert('net_cents', (v) => (v as num).toInt()),
      vatCents: $checkedConvert('vat_cents', (v) => (v as num).toInt()),
      createdAt: $checkedConvert(
        'created_at',
        (v) => DateTime.parse(v as String),
      ),
      processedAt: $checkedConvert(
        'processed_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      canCollect: $checkedConvert('can_collect', (v) => v as bool),
      lines: $checkedConvert(
        'lines',
        (v) => (v as List<dynamic>)
            .map((e) => VendorCounterLine.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      newlyCollected: $checkedConvert('newly_collected', (v) => v as bool?),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'vendorId': 'vendor_id',
    'orderNumber': 'order_number',
    'amountCents': 'amount_cents',
    'netCents': 'net_cents',
    'vatCents': 'vat_cents',
    'createdAt': 'created_at',
    'processedAt': 'processed_at',
    'canCollect': 'can_collect',
    'newlyCollected': 'newly_collected',
  },
);

Map<String, dynamic> _$VendorCounterReceiptToJson(
  VendorCounterReceipt instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'vendor_id': instance.vendorId,
  'id': instance.id,
  'order_number': instance.orderNumber,
  'status': _$VendorCounterReceiptStatusEnumEnumMap[instance.status]!,
  'currency': instance.currency,
  'amount_cents': instance.amountCents,
  'net_cents': instance.netCents,
  'vat_cents': instance.vatCents,
  'created_at': instance.createdAt.toIso8601String(),
  'processed_at': instance.processedAt?.toIso8601String(),
  'can_collect': instance.canCollect,
  'lines': instance.lines.map((e) => e.toJson()).toList(),
  'newly_collected': ?instance.newlyCollected,
};

const _$VendorCounterReceiptStatusEnumEnumMap = {
  VendorCounterReceiptStatusEnum.ready: 'ready',
  VendorCounterReceiptStatusEnum.collected: 'collected',
  VendorCounterReceiptStatusEnum.cancelled: 'cancelled',
  VendorCounterReceiptStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
