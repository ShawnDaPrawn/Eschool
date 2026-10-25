// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_consultation_access_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetConsultationAccessResponse _$GetConsultationAccessResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetConsultationAccessResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetConsultationAccessResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ConsultationAccess.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetConsultationAccessResponseToJson(
  GetConsultationAccessResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
