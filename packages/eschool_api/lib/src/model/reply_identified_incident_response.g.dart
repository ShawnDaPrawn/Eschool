// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reply_identified_incident_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReplyIdentifiedIncidentResponse _$ReplyIdentifiedIncidentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReplyIdentifiedIncidentResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReplyIdentifiedIncidentResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => IncidentReplyReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ReplyIdentifiedIncidentResponseToJson(
  ReplyIdentifiedIncidentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
