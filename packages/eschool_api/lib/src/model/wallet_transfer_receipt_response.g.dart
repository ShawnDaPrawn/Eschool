// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_transfer_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletTransferReceiptResponse _$WalletTransferReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletTransferReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletTransferReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletTransferReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletTransferReceiptResponseToJson(
  WalletTransferReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
