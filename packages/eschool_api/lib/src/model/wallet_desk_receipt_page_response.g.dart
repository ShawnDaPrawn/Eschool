// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_desk_receipt_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletDeskReceiptPageResponse _$WalletDeskReceiptPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletDeskReceiptPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletDeskReceiptPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletDeskReceiptPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletDeskReceiptPageResponseToJson(
  WalletDeskReceiptPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
