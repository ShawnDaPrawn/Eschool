// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_counter_payment_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCounterPaymentReceiptResponse
_$WalletCounterPaymentReceiptResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('WalletCounterPaymentReceiptResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = WalletCounterPaymentReceiptResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) =>
              WalletCounterPaymentReceipt.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$WalletCounterPaymentReceiptResponseToJson(
  WalletCounterPaymentReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
