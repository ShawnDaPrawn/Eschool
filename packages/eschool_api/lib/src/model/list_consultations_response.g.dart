// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_consultations_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListConsultationsResponse _$ListConsultationsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ListConsultationsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ListConsultationsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ConsultationPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ListConsultationsResponseToJson(
  ListConsultationsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
