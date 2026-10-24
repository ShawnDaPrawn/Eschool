// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_preview_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessagePreviewResponse
_$CommunicationMessagePreviewResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CommunicationMessagePreviewResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = CommunicationMessagePreviewResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) =>
              CommunicationMessagePreview.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$CommunicationMessagePreviewResponseToJson(
  CommunicationMessagePreviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
