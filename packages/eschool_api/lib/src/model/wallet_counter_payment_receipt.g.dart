// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_counter_payment_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCounterPaymentReceipt _$WalletCounterPaymentReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletCounterPaymentReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'vendor_id',
        'id',
        'intent_id',
        'operation_id',
        'status',
        'paid_at',
        'order',
      ],
    );
    final val = WalletCounterPaymentReceipt(
      replayed: $checkedConvert('replayed', (v) => v as bool?),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      id: $checkedConvert('id', (v) => v as String),
      intentId: $checkedConvert('intent_id', (v) => v as String),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WalletCounterPaymentReceiptStatusEnumEnumMap,
          v,
          unknownValue:
              WalletCounterPaymentReceiptStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      paidAt: $checkedConvert('paid_at', (v) => DateTime.parse(v as String)),
      order: $checkedConvert(
        'order',
        (v) => VendorCounterReceipt.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'vendorId': 'vendor_id',
    'intentId': 'intent_id',
    'operationId': 'operation_id',
    'paidAt': 'paid_at',
  },
);

Map<String, dynamic> _$WalletCounterPaymentReceiptToJson(
  WalletCounterPaymentReceipt instance,
) => <String, dynamic>{
  'replayed': ?instance.replayed,
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'vendor_id': instance.vendorId,
  'id': instance.id,
  'intent_id': instance.intentId,
  'operation_id': instance.operationId,
  'status': _$WalletCounterPaymentReceiptStatusEnumEnumMap[instance.status]!,
  'paid_at': instance.paidAt.toIso8601String(),
  'order': instance.order.toJson(),
};

const _$WalletCounterPaymentReceiptStatusEnumEnumMap = {
  WalletCounterPaymentReceiptStatusEnum.paid: 'paid',
  WalletCounterPaymentReceiptStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
