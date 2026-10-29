// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_order_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceOrderReceiptResponse _$CommerceOrderReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommerceOrderReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = CommerceOrderReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => CommerceOrderReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$CommerceOrderReceiptResponseToJson(
  CommerceOrderReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
