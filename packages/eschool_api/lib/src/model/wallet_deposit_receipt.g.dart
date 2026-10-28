// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_deposit_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletDepositReceipt _$WalletDepositReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletDepositReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'reference',
        'school_id',
        'actor_user_id',
        'operation_id',
        'wallet_id',
        'currency',
        'requested_cents',
        'requested_method',
        'requested_reference',
        'paid_on',
        'status',
        'reason_code',
        'created_at',
        'processed_at',
        'verified_receipt',
      ],
    );
    final val = WalletDepositReceipt(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      reference: $checkedConvert('reference', (v) => v as String),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      walletId: $checkedConvert('wallet_id', (v) => (v as num).toInt()),
      currency: $checkedConvert('currency', (v) => v as String),
      requestedCents: $checkedConvert(
        'requested_cents',
        (v) => (v as num).toInt(),
      ),
      requestedMethod: $checkedConvert(
        'requested_method',
        (v) => $enumDecode(
          _$WalletDepositReceiptRequestedMethodEnumEnumMap,
          v,
          unknownValue:
              WalletDepositReceiptRequestedMethodEnum.unknownDefaultOpenApi,
        ),
      ),
      requestedReference: $checkedConvert(
        'requested_reference',
        (v) => v as String,
      ),
      paidOn: $checkedConvert('paid_on', (v) => DateTime.parse(v as String)),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WalletDepositReceiptStatusEnumEnumMap,
          v,
          unknownValue: WalletDepositReceiptStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      reasonCode: $checkedConvert(
        'reason_code',
        (v) => $enumDecodeNullable(
          _$WalletDepositReceiptReasonCodeEnumEnumMap,
          v,
          unknownValue:
              WalletDepositReceiptReasonCodeEnum.unknownDefaultOpenApi,
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
      verifiedReceipt: $checkedConvert(
        'verified_receipt',
        (v) => v == null
            ? null
            : WalletDepositReceiptVerifiedReceipt.fromJson(
                v as Map<String, dynamic>,
              ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'operationId': 'operation_id',
    'walletId': 'wallet_id',
    'requestedCents': 'requested_cents',
    'requestedMethod': 'requested_method',
    'requestedReference': 'requested_reference',
    'paidOn': 'paid_on',
    'reasonCode': 'reason_code',
    'createdAt': 'created_at',
    'processedAt': 'processed_at',
    'verifiedReceipt': 'verified_receipt',
  },
);

Map<String, dynamic> _$WalletDepositReceiptToJson(
  WalletDepositReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'reference': instance.reference,
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'operation_id': instance.operationId,
  'wallet_id': instance.walletId,
  'currency': instance.currency,
  'requested_cents': instance.requestedCents,
  'requested_method':
      _$WalletDepositReceiptRequestedMethodEnumEnumMap[instance
          .requestedMethod]!,
  'requested_reference': instance.requestedReference,
  'paid_on': instance.paidOn.toIso8601String(),
  'status': _$WalletDepositReceiptStatusEnumEnumMap[instance.status]!,
  'reason_code':
      _$WalletDepositReceiptReasonCodeEnumEnumMap[instance.reasonCode],
  'created_at': instance.createdAt.toIso8601String(),
  'processed_at': instance.processedAt?.toIso8601String(),
  'verified_receipt': instance.verifiedReceipt?.toJson(),
};

const _$WalletDepositReceiptRequestedMethodEnumEnumMap = {
  WalletDepositReceiptRequestedMethodEnum.cash: 'cash',
  WalletDepositReceiptRequestedMethodEnum.eft: 'eft',
  WalletDepositReceiptRequestedMethodEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$WalletDepositReceiptStatusEnumEnumMap = {
  WalletDepositReceiptStatusEnum.pending: 'pending',
  WalletDepositReceiptStatusEnum.verified: 'verified',
  WalletDepositReceiptStatusEnum.cancelled: 'cancelled',
  WalletDepositReceiptStatusEnum.rejected: 'rejected',
  WalletDepositReceiptStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$WalletDepositReceiptReasonCodeEnumEnumMap = {
  WalletDepositReceiptReasonCodeEnum.verified: 'verified',
  WalletDepositReceiptReasonCodeEnum.withdrawn: 'withdrawn',
  WalletDepositReceiptReasonCodeEnum.notVerified: 'not_verified',
  WalletDepositReceiptReasonCodeEnum.duplicate: 'duplicate',
  WalletDepositReceiptReasonCodeEnum.requestIncorrect: 'request_incorrect',
  WalletDepositReceiptReasonCodeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
