// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_group_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityGroupResponse _$ActivityGroupResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityGroupResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ActivityGroupResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ActivityGroup.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ActivityGroupResponseToJson(
  ActivityGroupResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
