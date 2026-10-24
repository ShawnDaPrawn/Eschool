// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentReceiptResponse _$ContentReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ContentReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ContentReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ContentReceipt.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ContentReceiptResponseToJson(
  ContentReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
