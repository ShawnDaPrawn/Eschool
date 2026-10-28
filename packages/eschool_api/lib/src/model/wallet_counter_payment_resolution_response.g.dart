// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_counter_payment_resolution_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCounterPaymentResolutionResponse
_$WalletCounterPaymentResolutionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletCounterPaymentResolutionResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletCounterPaymentResolutionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletCounterPaymentResolution.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletCounterPaymentResolutionResponseToJson(
  WalletCounterPaymentResolutionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
