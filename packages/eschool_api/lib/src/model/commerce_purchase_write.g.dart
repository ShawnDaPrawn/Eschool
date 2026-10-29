// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_purchase_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommercePurchaseWrite _$CommercePurchaseWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommercePurchaseWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'quote', 'confirmed']);
  final val = CommercePurchaseWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    quote: $checkedConvert('quote', (v) => v as String),
    confirmed: $checkedConvert('confirmed', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$CommercePurchaseWriteToJson(
  CommercePurchaseWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'quote': instance.quote,
  'confirmed': instance.confirmed,
};
