// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'text_messaging_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TextMessagingResponse _$TextMessagingResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TextMessagingResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = TextMessagingResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => TextMessagingResponseData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$TextMessagingResponseToJson(
  TextMessagingResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
