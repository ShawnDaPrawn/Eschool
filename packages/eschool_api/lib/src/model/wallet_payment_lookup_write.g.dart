// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_payment_lookup_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletPaymentLookupWrite _$WalletPaymentLookupWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletPaymentLookupWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['qr_token']);
  final val = WalletPaymentLookupWrite(
    qrToken: $checkedConvert('qr_token', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'qrToken': 'qr_token'});

Map<String, dynamic> _$WalletPaymentLookupWriteToJson(
  WalletPaymentLookupWrite instance,
) => <String, dynamic>{'qr_token': instance.qrToken};
