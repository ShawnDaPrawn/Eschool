// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_deposit_cancel_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletDepositCancelWrite _$WalletDepositCancelWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletDepositCancelWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'confirmed']);
  final val = WalletDepositCancelWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    confirmed: $checkedConvert('confirmed', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$WalletDepositCancelWriteToJson(
  WalletDepositCancelWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'confirmed': instance.confirmed,
};
