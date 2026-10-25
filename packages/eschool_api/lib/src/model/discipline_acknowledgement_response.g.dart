// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_acknowledgement_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineAcknowledgementResponse _$DisciplineAcknowledgementResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineAcknowledgementResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineAcknowledgementResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineAcknowledgement.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineAcknowledgementResponseToJson(
  DisciplineAcknowledgementResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
