// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child_mode_session_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChildModeSessionResponse _$ChildModeSessionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ChildModeSessionResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ChildModeSessionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ChildModeSession.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
    ),
  );
  return val;
});

Map<String, dynamic> _$ChildModeSessionResponseToJson(
  ChildModeSessionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code?.toJson(),
};
