// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_settlement_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorSettlementSummary _$VendorSettlementSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'VendorSettlementSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
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
      ],
    );
    final val = VendorSettlementSummary(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      method: $checkedConvert(
        'method',
        (v) => $enumDecode(
          _$VendorSettlementSummaryMethodEnumEnumMap,
          v,
          unknownValue: VendorSettlementSummaryMethodEnum.unknownDefaultOpenApi,
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
          _$VendorSettlementSummaryCollectedByEnumEnumMap,
          v,
          unknownValue:
              VendorSettlementSummaryCollectedByEnum.unknownDefaultOpenApi,
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
          _$VendorSettlementSummaryStatusEnumEnumMap,
          v,
          unknownValue: VendorSettlementSummaryStatusEnum.unknownDefaultOpenApi,
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
    );
    return val;
  },
  fieldKeyMap: const {
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

Map<String, dynamic> _$VendorSettlementSummaryToJson(
  VendorSettlementSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'method': _$VendorSettlementSummaryMethodEnumEnumMap[instance.method]!,
  'currency': instance.currency,
  'period_from': instance.periodFrom.toIso8601String(),
  'period_to': instance.periodTo.toIso8601String(),
  'order_count': instance.orderCount,
  'gross_cents': instance.grossCents,
  'sales_net_cents': instance.salesNetCents,
  'vat_cents': instance.vatCents,
  'collected_by':
      _$VendorSettlementSummaryCollectedByEnumEnumMap[instance.collectedBy]!,
  'collector_fee_cents': instance.collectorFeeCents,
  'payout_cents': instance.payoutCents,
  'status': _$VendorSettlementSummaryStatusEnumEnumMap[instance.status]!,
  'created_at': instance.createdAt.toIso8601String(),
  'settled_at': instance.settledAt?.toIso8601String(),
  'paid_on': instance.paidOn?.toIso8601String(),
};

const _$VendorSettlementSummaryMethodEnumEnumMap = {
  VendorSettlementSummaryMethodEnum.schoolInternal: 'school_internal',
  VendorSettlementSummaryMethodEnum.cash: 'cash',
  VendorSettlementSummaryMethodEnum.eft: 'eft',
  VendorSettlementSummaryMethodEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$VendorSettlementSummaryCollectedByEnumEnumMap = {
  VendorSettlementSummaryCollectedByEnum.school: 'school',
  VendorSettlementSummaryCollectedByEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$VendorSettlementSummaryStatusEnumEnumMap = {
  VendorSettlementSummaryStatusEnum.prepared: 'prepared',
  VendorSettlementSummaryStatusEnum.approved: 'approved',
  VendorSettlementSummaryStatusEnum.settled: 'settled',
  VendorSettlementSummaryStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
