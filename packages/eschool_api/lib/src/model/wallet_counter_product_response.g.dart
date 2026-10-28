// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_counter_product_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCounterProductResponse _$WalletCounterProductResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletCounterProductResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletCounterProductResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletCounterProduct.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletCounterProductResponseToJson(
  WalletCounterProductResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
