// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_withdrawal_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletWithdrawalReceipt _$WalletWithdrawalReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletWithdrawalReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'reference',
        'school_id',
        'holder_user_id',
        'operation_id',
        'wallet_id',
        'currency',
        'amount_cents',
        'method',
        'status',
        'created_at',
        'processed_at',
        'pickup_starts_at',
        'pickup_ends_at',
        'paid_on',
        'payout_reference',
        'pickup_label',
      ],
    );
    final val = WalletWithdrawalReceipt(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      reference: $checkedConvert('reference', (v) => v as String),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      holderUserId: $checkedConvert(
        'holder_user_id',
        (v) => (v as num).toInt(),
      ),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      walletId: $checkedConvert('wallet_id', (v) => (v as num).toInt()),
      currency: $checkedConvert('currency', (v) => v as String),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      method: $checkedConvert(
        'method',
        (v) => $enumDecode(
          _$WalletWithdrawalReceiptMethodEnumEnumMap,
          v,
          unknownValue: WalletWithdrawalReceiptMethodEnum.unknownDefaultOpenApi,
        ),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WalletWithdrawalReceiptStatusEnumEnumMap,
          v,
          unknownValue: WalletWithdrawalReceiptStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      createdAt: $checkedConvert(
        'created_at',
        (v) => DateTime.parse(v as String),
      ),
      processedAt: $checkedConvert(
        'processed_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      pickupStartsAt: $checkedConvert(
        'pickup_starts_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      pickupEndsAt: $checkedConvert(
        'pickup_ends_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      paidOn: $checkedConvert(
        'paid_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      payoutReference: $checkedConvert('payout_reference', (v) => v as String?),
      pickupLabel: $checkedConvert('pickup_label', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'holderUserId': 'holder_user_id',
    'operationId': 'operation_id',
    'walletId': 'wallet_id',
    'amountCents': 'amount_cents',
    'createdAt': 'created_at',
    'processedAt': 'processed_at',
    'pickupStartsAt': 'pickup_starts_at',
    'pickupEndsAt': 'pickup_ends_at',
    'paidOn': 'paid_on',
    'payoutReference': 'payout_reference',
    'pickupLabel': 'pickup_label',
  },
);

Map<String, dynamic> _$WalletWithdrawalReceiptToJson(
  WalletWithdrawalReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'reference': instance.reference,
  'school_id': instance.schoolId,
  'holder_user_id': instance.holderUserId,
  'operation_id': instance.operationId,
  'wallet_id': instance.walletId,
  'currency': instance.currency,
  'amount_cents': instance.amountCents,
  'method': _$WalletWithdrawalReceiptMethodEnumEnumMap[instance.method]!,
  'status': _$WalletWithdrawalReceiptStatusEnumEnumMap[instance.status]!,
  'created_at': instance.createdAt.toIso8601String(),
  'processed_at': instance.processedAt?.toIso8601String(),
  'pickup_starts_at': instance.pickupStartsAt?.toIso8601String(),
  'pickup_ends_at': instance.pickupEndsAt?.toIso8601String(),
  'paid_on': instance.paidOn?.toIso8601String(),
  'payout_reference': instance.payoutReference,
  'pickup_label': instance.pickupLabel,
};

const _$WalletWithdrawalReceiptMethodEnumEnumMap = {
  WalletWithdrawalReceiptMethodEnum.cash: 'cash',
  WalletWithdrawalReceiptMethodEnum.eft: 'eft',
  WalletWithdrawalReceiptMethodEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$WalletWithdrawalReceiptStatusEnumEnumMap = {
  WalletWithdrawalReceiptStatusEnum.requested: 'requested',
  WalletWithdrawalReceiptStatusEnum.approved: 'approved',
  WalletWithdrawalReceiptStatusEnum.paid: 'paid',
  WalletWithdrawalReceiptStatusEnum.cancelled: 'cancelled',
  WalletWithdrawalReceiptStatusEnum.rejected: 'rejected',
  WalletWithdrawalReceiptStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
