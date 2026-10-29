// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_payment_personal_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletPaymentPersonalPageResponse _$WalletPaymentPersonalPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletPaymentPersonalPageResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletPaymentPersonalPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletPaymentPersonalPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletPaymentPersonalPageResponseToJson(
  WalletPaymentPersonalPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
