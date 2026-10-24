// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_read_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentReadReceiptResponse _$ContentReadReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ContentReadReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ContentReadReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ContentReadReceipt.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ContentReadReceiptResponseToJson(
  ContentReadReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
