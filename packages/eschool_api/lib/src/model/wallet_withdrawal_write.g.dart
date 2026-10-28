// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_withdrawal_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletWithdrawalWrite _$WalletWithdrawalWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WalletWithdrawalWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'amount', 'confirmed'],
    );
    final val = WalletWithdrawalWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      amount: $checkedConvert('amount', (v) => v as String),
      slotId: $checkedConvert('slot_id', (v) => (v as num?)?.toInt()),
      privateBankInstructions: $checkedConvert(
        'private_bank_instructions',
        (v) => v as String?,
      ),
      confirmed: $checkedConvert('confirmed', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'slotId': 'slot_id',
    'privateBankInstructions': 'private_bank_instructions',
  },
);

Map<String, dynamic> _$WalletWithdrawalWriteToJson(
  WalletWithdrawalWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'amount': instance.amount,
  'slot_id': ?instance.slotId,
  'private_bank_instructions': ?instance.privateBankInstructions,
  'confirmed': instance.confirmed,
};
