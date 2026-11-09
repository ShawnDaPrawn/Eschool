// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child_mode_start_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChildModeStartResponse _$ChildModeStartResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ChildModeStartResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ChildModeStartResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ChildModeStartResponseData.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
    ),
  );
  return val;
});

Map<String, dynamic> _$ChildModeStartResponseToJson(
  ChildModeStartResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code?.toJson(),
};
