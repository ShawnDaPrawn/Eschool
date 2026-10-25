// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_session_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivitySessionResponse _$ActivitySessionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivitySessionResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ActivitySessionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ActivitySession.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ActivitySessionResponseToJson(
  ActivitySessionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
