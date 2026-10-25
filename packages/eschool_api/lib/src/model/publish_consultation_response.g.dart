// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'publish_consultation_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PublishConsultationResponse _$PublishConsultationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PublishConsultationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PublishConsultationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ConsultationReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$PublishConsultationResponseToJson(
  PublishConsultationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
