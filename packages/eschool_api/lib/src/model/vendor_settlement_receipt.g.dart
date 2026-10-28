// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_settlement_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorSettlementReceipt _$VendorSettlementReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'VendorSettlementReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'vendor_id',
        'id',
        'method',
        'currency',
        'period_from',
        'period_to',
        'order_count',
        'gross_cents',
        'sales_net_cents',
        'vat_cents',
        'collected_by',
        'collector_fee_cents',
        'payout_cents',
        'status',
        'created_at',
        'settled_at',
        'paid_on',
        'lines',
      ],
    );
    final val = VendorSettlementReceipt(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      method: $checkedConvert(
        'method',
        (v) => $enumDecode(
          _$VendorSettlementReceiptMethodEnumEnumMap,
          v,
          unknownValue: VendorSettlementReceiptMethodEnum.unknownDefaultOpenApi,
        ),
      ),
      currency: $checkedConvert('currency', (v) => v as String),
      periodFrom: $checkedConvert(
        'period_from',
        (v) => DateTime.parse(v as String),
      ),
      periodTo: $checkedConvert(
        'period_to',
        (v) => DateTime.parse(v as String),
      ),
      orderCount: $checkedConvert('order_count', (v) => (v as num).toInt()),
      grossCents: $checkedConvert('gross_cents', (v) => (v as num).toInt()),
      salesNetCents: $checkedConvert(
        'sales_net_cents',
        (v) => (v as num).toInt(),
      ),
      vatCents: $checkedConvert('vat_cents', (v) => (v as num).toInt()),
      collectedBy: $checkedConvert(
        'collected_by',
        (v) => $enumDecode(
          _$VendorSettlementReceiptCollectedByEnumEnumMap,
          v,
          unknownValue:
              VendorSettlementReceiptCollectedByEnum.unknownDefaultOpenApi,
        ),
      ),
      collectorFeeCents: $checkedConvert(
        'collector_fee_cents',
        (v) => (v as num).toInt(),
      ),
      payoutCents: $checkedConvert('payout_cents', (v) => (v as num).toInt()),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$VendorSettlementReceiptStatusEnumEnumMap,
          v,
          unknownValue: VendorSettlementReceiptStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      createdAt: $checkedConvert(
        'created_at',
        (v) => DateTime.parse(v as String),
      ),
      settledAt: $checkedConvert(
        'settled_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      paidOn: $checkedConvert(
        'paid_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      lines: $checkedConvert(
        'lines',
        (v) => (v as List<dynamic>)
            .map(
              (e) => VendorSettlementLine.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'vendorId': 'vendor_id',
    'periodFrom': 'period_from',
    'periodTo': 'period_to',
    'orderCount': 'order_count',
    'grossCents': 'gross_cents',
    'salesNetCents': 'sales_net_cents',
    'vatCents': 'vat_cents',
    'collectedBy': 'collected_by',
    'collectorFeeCents': 'collector_fee_cents',
    'payoutCents': 'payout_cents',
    'createdAt': 'created_at',
    'settledAt': 'settled_at',
    'paidOn': 'paid_on',
  },
);

Map<String, dynamic> _$VendorSettlementReceiptToJson(
  VendorSettlementReceipt instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'vendor_id': instance.vendorId,
  'id': instance.id,
  'method': _$VendorSettlementReceiptMethodEnumEnumMap[instance.method]!,
  'currency': instance.currency,
  'period_from': instance.periodFrom.toIso8601String(),
  'period_to': instance.periodTo.toIso8601String(),
  'order_count': instance.orderCount,
  'gross_cents': instance.grossCents,
  'sales_net_cents': instance.salesNetCents,
  'vat_cents': instance.vatCents,
  'collected_by':
      _$VendorSettlementReceiptCollectedByEnumEnumMap[instance.collectedBy]!,
  'collector_fee_cents': instance.collectorFeeCents,
  'payout_cents': instance.payoutCents,
  'status': _$VendorSettlementReceiptStatusEnumEnumMap[instance.status]!,
  'created_at': instance.createdAt.toIso8601String(),
  'settled_at': instance.settledAt?.toIso8601String(),
  'paid_on': instance.paidOn?.toIso8601String(),
  'lines': instance.lines.map((e) => e.toJson()).toList(),
};

const _$VendorSettlementReceiptMethodEnumEnumMap = {
  VendorSettlementReceiptMethodEnum.schoolInternal: 'school_internal',
  VendorSettlementReceiptMethodEnum.cash: 'cash',
  VendorSettlementReceiptMethodEnum.eft: 'eft',
  VendorSettlementReceiptMethodEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$VendorSettlementReceiptCollectedByEnumEnumMap = {
  VendorSettlementReceiptCollectedByEnum.school: 'school',
  VendorSettlementReceiptCollectedByEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$VendorSettlementReceiptStatusEnumEnumMap = {
  VendorSettlementReceiptStatusEnum.prepared: 'prepared',
  VendorSettlementReceiptStatusEnum.approved: 'approved',
  VendorSettlementReceiptStatusEnum.settled: 'settled',
  VendorSettlementReceiptStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
