// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_audience_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationAudienceListResponse _$CommunicationAudienceListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommunicationAudienceListResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CommunicationAudienceListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CommunicationAudienceList.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CommunicationAudienceListResponseToJson(
  CommunicationAudienceListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
