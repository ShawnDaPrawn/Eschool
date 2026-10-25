// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reply_anonymous_incident_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReplyAnonymousIncidentResponse _$ReplyAnonymousIncidentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReplyAnonymousIncidentResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReplyAnonymousIncidentResponse(
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

Map<String, dynamic> _$ReplyAnonymousIncidentResponseToJson(
  ReplyAnonymousIncidentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
