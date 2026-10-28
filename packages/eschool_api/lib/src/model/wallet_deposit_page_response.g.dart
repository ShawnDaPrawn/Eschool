// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_deposit_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletDepositPageResponse _$WalletDepositPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletDepositPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletDepositPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletDepositPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletDepositPageResponseToJson(
  WalletDepositPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
