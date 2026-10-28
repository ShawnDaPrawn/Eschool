// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_payment_personal_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletPaymentPersonalResponse _$WalletPaymentPersonalResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletPaymentPersonalResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletPaymentPersonalResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletPaymentPersonal.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletPaymentPersonalResponseToJson(
  WalletPaymentPersonalResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
