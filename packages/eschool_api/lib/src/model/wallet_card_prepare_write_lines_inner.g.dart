// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_card_prepare_write_lines_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCardPrepareWriteLinesInner _$WalletCardPrepareWriteLinesInnerFromJson(
  Map<String, dynamic> json,
) =>
    $checkedCreate('WalletCardPrepareWriteLinesInner', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['product_id', 'quantity']);
      final val = WalletCardPrepareWriteLinesInner(
        productId: $checkedConvert('product_id', (v) => (v as num).toInt()),
        quantity: $checkedConvert('quantity', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'productId': 'product_id'});

Map<String, dynamic> _$WalletCardPrepareWriteLinesInnerToJson(
  WalletCardPrepareWriteLinesInner instance,
) => <String, dynamic>{
  'product_id': instance.productId,
  'quantity': instance.quantity,
};
