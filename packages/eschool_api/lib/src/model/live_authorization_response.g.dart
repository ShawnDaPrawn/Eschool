// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'live_authorization_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LiveAuthorizationResponse _$LiveAuthorizationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LiveAuthorizationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = LiveAuthorizationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => LiveAuthorization.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$LiveAuthorizationResponseToJson(
  LiveAuthorizationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
