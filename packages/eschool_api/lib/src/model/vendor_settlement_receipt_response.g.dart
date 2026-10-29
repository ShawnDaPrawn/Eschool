// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_settlement_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorSettlementReceiptResponse _$VendorSettlementReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('VendorSettlementReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = VendorSettlementReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => VendorSettlementReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$VendorSettlementReceiptResponseToJson(
  VendorSettlementReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
