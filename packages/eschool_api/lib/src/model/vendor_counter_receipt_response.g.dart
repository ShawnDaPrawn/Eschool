// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_counter_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorCounterReceiptResponse _$VendorCounterReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('VendorCounterReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = VendorCounterReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => VendorCounterReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$VendorCounterReceiptResponseToJson(
  VendorCounterReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
