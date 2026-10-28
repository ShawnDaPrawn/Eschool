// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_transfer_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletTransferReceipt _$WalletTransferReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletTransferReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'school_id',
        'actor_user_id',
        'from_wallet_id',
        'to_wallet_id',
        'from_owner_id',
        'to_owner_id',
        'reference',
        'operation_id',
        'kind',
        'currency',
        'amount_cents',
        'created_at',
      ],
    );
    final val = WalletTransferReceipt(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      fromWalletId: $checkedConvert(
        'from_wallet_id',
        (v) => (v as num).toInt(),
      ),
      toWalletId: $checkedConvert('to_wallet_id', (v) => (v as num).toInt()),
      fromOwnerId: $checkedConvert('from_owner_id', (v) => (v as num).toInt()),
      toOwnerId: $checkedConvert('to_owner_id', (v) => (v as num).toInt()),
      reference: $checkedConvert('reference', (v) => v as String),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$WalletTransferReceiptKindEnumEnumMap,
          v,
          unknownValue: WalletTransferReceiptKindEnum.unknownDefaultOpenApi,
        ),
      ),
      currency: $checkedConvert('currency', (v) => v as String),
      amountCents: $checkedConvert('amount_cents', (v) => (v as num).toInt()),
      createdAt: $checkedConvert(
        'created_at',
        (v) => DateTime.parse(v as String),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'fromWalletId': 'from_wallet_id',
    'toWalletId': 'to_wallet_id',
    'fromOwnerId': 'from_owner_id',
    'toOwnerId': 'to_owner_id',
    'operationId': 'operation_id',
    'amountCents': 'amount_cents',
    'createdAt': 'created_at',
  },
);

Map<String, dynamic> _$WalletTransferReceiptToJson(
  WalletTransferReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'from_wallet_id': instance.fromWalletId,
  'to_wallet_id': instance.toWalletId,
  'from_owner_id': instance.fromOwnerId,
  'to_owner_id': instance.toOwnerId,
  'reference': instance.reference,
  'operation_id': instance.operationId,
  'kind': _$WalletTransferReceiptKindEnumEnumMap[instance.kind]!,
  'currency': instance.currency,
  'amount_cents': instance.amountCents,
  'created_at': instance.createdAt.toIso8601String(),
};

const _$WalletTransferReceiptKindEnumEnumMap = {
  WalletTransferReceiptKindEnum.toChild: 'to_child',
  WalletTransferReceiptKindEnum.toParent: 'to_parent',
  WalletTransferReceiptKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
