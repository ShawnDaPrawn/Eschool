// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_order_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceOrderReceipt _$CommerceOrderReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommerceOrderReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'school_id',
        'actor_user_id',
        'operation_id',
        'order_number',
        'wallet_id',
        'wallet_owner_id',
        'learner_id',
        'vendor_id',
        'currency',
        'amount_cents',
        'net_cents',
        'vat_cents',
        'status',
        'can_cancel',
        'qr',
        'created_at',
        'processed_at',
        'seller',
        'lines',
      ],
    );
    final val = CommerceOrderReceipt(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      orderNumber: $checkedConvert('order_number', (v) => v as String),
      walletId: $checkedConvert('wallet_id', (v) => (v as num).toInt()),
      walletOwnerId: $checkedConvert(
        'wallet_owner_id',
        (v) => (v as num).toInt(),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      currency: $checkedConvert('currency', (v) => v as String),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      netCents: $checkedConvert('net_cents', (v) => (v as num).toInt()),
      vatCents: $checkedConvert('vat_cents', (v) => (v as num).toInt()),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$CommerceOrderReceiptStatusEnumEnumMap,
          v,
          unknownValue: CommerceOrderReceiptStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      canCancel: $checkedConvert('can_cancel', (v) => v as bool),
      qr: $checkedConvert('qr', (v) => v as String?),
      createdAt: $checkedConvert(
        'created_at',
        (v) => DateTime.parse(v as String),
      ),
      processedAt: $checkedConvert(
        'processed_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      seller: $checkedConvert(
        'seller',
        (v) => CommerceSeller.fromJson(v as Map<String, dynamic>),
      ),
      lines: $checkedConvert(
        'lines',
        (v) => (v as List<dynamic>)
            .map((e) => CommerceReceiptLine.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      fulfilmentKind: $checkedConvert(
        'fulfilment_kind',
        (v) => $enumDecodeNullable(
          _$CommerceOrderReceiptFulfilmentKindEnumEnumMap,
          v,
          unknownValue:
              CommerceOrderReceiptFulfilmentKindEnum.unknownDefaultOpenApi,
        ),
      ),
      ticketCount: $checkedConvert('ticket_count', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'operationId': 'operation_id',
    'orderNumber': 'order_number',
    'walletId': 'wallet_id',
    'walletOwnerId': 'wallet_owner_id',
    'learnerId': 'learner_id',
    'vendorId': 'vendor_id',
    'amountCents': 'amount_cents',
    'netCents': 'net_cents',
    'vatCents': 'vat_cents',
    'canCancel': 'can_cancel',
    'createdAt': 'created_at',
    'processedAt': 'processed_at',
    'fulfilmentKind': 'fulfilment_kind',
    'ticketCount': 'ticket_count',
  },
);

Map<String, dynamic> _$CommerceOrderReceiptToJson(
  CommerceOrderReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'operation_id': instance.operationId,
  'order_number': instance.orderNumber,
  'wallet_id': instance.walletId,
  'wallet_owner_id': instance.walletOwnerId,
  'learner_id': instance.learnerId,
  'vendor_id': instance.vendorId,
  'currency': instance.currency,
  'amount_cents': instance.amountCents,
  'net_cents': instance.netCents,
  'vat_cents': instance.vatCents,
  'status': _$CommerceOrderReceiptStatusEnumEnumMap[instance.status]!,
  'can_cancel': instance.canCancel,
  'qr': instance.qr,
  'created_at': instance.createdAt.toIso8601String(),
  'processed_at': instance.processedAt?.toIso8601String(),
  'seller': instance.seller.toJson(),
  'lines': instance.lines.map((e) => e.toJson()).toList(),
  'fulfilment_kind':
      ?_$CommerceOrderReceiptFulfilmentKindEnumEnumMap[instance.fulfilmentKind],
  'ticket_count': ?instance.ticketCount,
};

const _$CommerceOrderReceiptStatusEnumEnumMap = {
  CommerceOrderReceiptStatusEnum.ready: 'ready',
  CommerceOrderReceiptStatusEnum.collected: 'collected',
  CommerceOrderReceiptStatusEnum.cancelled: 'cancelled',
  CommerceOrderReceiptStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$CommerceOrderReceiptFulfilmentKindEnumEnumMap = {
  CommerceOrderReceiptFulfilmentKindEnum.physicalGoods: 'physical_goods',
  CommerceOrderReceiptFulfilmentKindEnum.digitalTickets: 'digital_tickets',
  CommerceOrderReceiptFulfilmentKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
