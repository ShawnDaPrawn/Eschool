// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'live_presence_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LivePresenceResponse _$LivePresenceResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LivePresenceResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = LivePresenceResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => LivePresence.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$LivePresenceResponseToJson(
  LivePresenceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
