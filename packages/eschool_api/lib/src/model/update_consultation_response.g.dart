// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'update_consultation_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UpdateConsultationResponse _$UpdateConsultationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('UpdateConsultationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = UpdateConsultationResponse(
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

Map<String, dynamic> _$UpdateConsultationResponseToJson(
  UpdateConsultationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
