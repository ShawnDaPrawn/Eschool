// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_counter_collect.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorCounterCollect _$VendorCounterCollectFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'VendorCounterCollect',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['operation_id', 'confirmed']);
    final val = VendorCounterCollect(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      orderNumber: $checkedConvert('order_number', (v) => v as String?),
      qrToken: $checkedConvert('qr_token', (v) => v as String?),
      confirmed: $checkedConvert('confirmed', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'orderNumber': 'order_number',
    'qrToken': 'qr_token',
  },
);

Map<String, dynamic> _$VendorCounterCollectToJson(
  VendorCounterCollect instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'order_number': ?instance.orderNumber,
  'qr_token': ?instance.qrToken,
  'confirmed': instance.confirmed,
};
