// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_read_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationReadReceiptResponse _$CommunicationReadReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationReadReceiptResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CommunicationReadReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CommunicationReadReceipt.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationReadReceiptResponseToJson(
  CommunicationReadReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
