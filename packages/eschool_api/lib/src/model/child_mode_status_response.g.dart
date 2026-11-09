// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child_mode_status_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChildModeStatusResponse _$ChildModeStatusResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ChildModeStatusResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ChildModeStatusResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ChildModeStatus.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
    ),
  );
  return val;
});

Map<String, dynamic> _$ChildModeStatusResponseToJson(
  ChildModeStatusResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code?.toJson(),
};
