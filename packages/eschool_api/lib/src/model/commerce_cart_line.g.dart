// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_cart_line.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceCartLine _$CommerceCartLineFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CommerceCartLine', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['product_id', 'quantity']);
      final val = CommerceCartLine(
        productId: $checkedConvert('product_id', (v) => (v as num).toInt()),
        quantity: $checkedConvert('quantity', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'productId': 'product_id'});

Map<String, dynamic> _$CommerceCartLineToJson(CommerceCartLine instance) =>
    <String, dynamic>{
      'product_id': instance.productId,
      'quantity': instance.quantity,
    };
