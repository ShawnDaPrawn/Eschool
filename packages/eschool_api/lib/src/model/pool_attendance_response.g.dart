// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_attendance_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolAttendanceResponse _$PoolAttendanceResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PoolAttendanceResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PoolAttendanceResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PoolAttendanceList.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => v == null ? null : PoolMemberPageResponseCode.fromJson(v),
    ),
  );
  return val;
});

Map<String, dynamic> _$PoolAttendanceResponseToJson(
  PoolAttendanceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code?.toJson(),
};
