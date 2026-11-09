// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child_mode_ended_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChildModeEndedResponse _$ChildModeEndedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ChildModeEndedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ChildModeEndedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ChildModeEndedResponseData.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
    ),
  );
  return val;
});

Map<String, dynamic> _$ChildModeEndedResponseToJson(
  ChildModeEndedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code?.toJson(),
};
