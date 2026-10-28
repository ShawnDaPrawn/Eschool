// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_withdrawal_cancel_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletWithdrawalCancelWrite _$WalletWithdrawalCancelWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletWithdrawalCancelWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'private_reason', 'confirmed'],
    );
    final val = WalletWithdrawalCancelWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      privateReason: $checkedConvert('private_reason', (v) => v as String),
      confirmed: $checkedConvert('confirmed', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'privateReason': 'private_reason',
  },
);

Map<String, dynamic> _$WalletWithdrawalCancelWriteToJson(
  WalletWithdrawalCancelWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'private_reason': instance.privateReason,
  'confirmed': instance.confirmed,
};
