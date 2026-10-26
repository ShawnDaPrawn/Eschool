// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_encoding_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureEncodingReceiptResponse _$SecureEncodingReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureEncodingReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureEncodingReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureEncodingReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SecureEncodingReceiptResponseToJson(
  SecureEncodingReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
