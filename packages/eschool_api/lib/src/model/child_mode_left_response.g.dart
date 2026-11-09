// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child_mode_left_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChildModeLeftResponse _$ChildModeLeftResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ChildModeLeftResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ChildModeLeftResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert('data', (v) => v),
    code: $checkedConvert(
      'code',
      (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
    ),
  );
  return val;
});

Map<String, dynamic> _$ChildModeLeftResponseToJson(
  ChildModeLeftResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data,
  'code': instance.code?.toJson(),
};
