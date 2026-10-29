// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_counter_catalogue_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCounterCatalogueResponse _$WalletCounterCatalogueResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletCounterCatalogueResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletCounterCatalogueResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletCounterCatalogue.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletCounterCatalogueResponseToJson(
  WalletCounterCatalogueResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
