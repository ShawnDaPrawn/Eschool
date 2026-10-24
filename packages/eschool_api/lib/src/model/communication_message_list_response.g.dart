// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_message_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationMessageListResponse _$CommunicationMessageListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationMessageListResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CommunicationMessageListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CommunicationMessageList.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationMessageListResponseToJson(
  CommunicationMessageListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
