// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_audience_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationAudienceReceiptResponse
_$CommunicationAudienceReceiptResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CommunicationAudienceReceiptResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = CommunicationAudienceReceiptResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) =>
              CommunicationAudienceReceipt.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$CommunicationAudienceReceiptResponseToJson(
  CommunicationAudienceReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
