// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_write_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferWriteReceiptResponse _$TransferWriteReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TransferWriteReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = TransferWriteReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => TransferWriteReceipt.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$TransferWriteReceiptResponseToJson(
  TransferWriteReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
