// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_transfer_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletTransferPageResponse _$WalletTransferPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletTransferPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletTransferPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletTransferPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletTransferPageResponseToJson(
  WalletTransferPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
