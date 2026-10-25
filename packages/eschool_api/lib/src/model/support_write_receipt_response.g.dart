// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_write_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportWriteReceiptResponse _$SupportWriteReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SupportWriteReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = SupportWriteReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => SupportWriteReceipt.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SupportWriteReceiptResponseToJson(
  SupportWriteReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
