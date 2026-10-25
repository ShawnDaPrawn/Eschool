// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_group_roster_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityGroupRosterResponse _$ActivityGroupRosterResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityGroupRosterResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ActivityGroupRosterResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ActivityGroupRoster.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ActivityGroupRosterResponseToJson(
  ActivityGroupRosterResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
