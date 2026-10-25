// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_consultation_responses_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListConsultationResponsesResponse _$ListConsultationResponsesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ListConsultationResponsesResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ListConsultationResponsesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ConsultationResponsePage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ListConsultationResponsesResponseToJson(
  ListConsultationResponsesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
