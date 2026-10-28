// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_withdrawal_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletWithdrawalReceiptResponse _$WalletWithdrawalReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletWithdrawalReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletWithdrawalReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletWithdrawalReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletWithdrawalReceiptResponseToJson(
  WalletWithdrawalReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
