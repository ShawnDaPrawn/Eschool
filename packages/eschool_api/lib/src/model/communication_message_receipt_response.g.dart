// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessageReceiptResponse
_$CommunicationMessageReceiptResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CommunicationMessageReceiptResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = CommunicationMessageReceiptResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) =>
              CommunicationMessageReceipt.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$CommunicationMessageReceiptResponseToJson(
  CommunicationMessageReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
