// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_transfer_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletTransferWrite _$WalletTransferWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'WalletTransferWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'from_wallet_id',
            'to_wallet_id',
            'amount',
            'confirmed',
          ],
        );
        final val = WalletTransferWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          fromWalletId: $checkedConvert(
            'from_wallet_id',
            (v) => (v as num).toInt(),
          ),
          toWalletId: $checkedConvert(
            'to_wallet_id',
            (v) => (v as num).toInt(),
          ),
          amount: $checkedConvert('amount', (v) => v as String),
          confirmed: $checkedConvert('confirmed', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'fromWalletId': 'from_wallet_id',
        'toWalletId': 'to_wallet_id',
      },
    );

Map<String, dynamic> _$WalletTransferWriteToJson(
  WalletTransferWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'from_wallet_id': instance.fromWalletId,
  'to_wallet_id': instance.toWalletId,
  'amount': instance.amount,
  'confirmed': instance.confirmed,
};
