// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_consultation_audience_choices_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListConsultationAudienceChoicesResponse
_$ListConsultationAudienceChoicesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ListConsultationAudienceChoicesResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ListConsultationAudienceChoicesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ConsultationAudienceChoicePage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ListConsultationAudienceChoicesResponseToJson(
  ListConsultationAudienceChoicesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
