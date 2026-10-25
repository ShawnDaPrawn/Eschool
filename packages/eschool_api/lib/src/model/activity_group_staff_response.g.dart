// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_group_staff_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityGroupStaffResponse _$ActivityGroupStaffResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityGroupStaffResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ActivityGroupStaffResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ActivityGroupStaff.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ActivityGroupStaffResponseToJson(
  ActivityGroupStaffResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
