// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_withdrawal_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletWithdrawalPageResponse _$WalletWithdrawalPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletWithdrawalPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletWithdrawalPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletWithdrawalPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletWithdrawalPageResponseToJson(
  WalletWithdrawalPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
