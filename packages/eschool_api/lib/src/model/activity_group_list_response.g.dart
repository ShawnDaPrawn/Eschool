// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_group_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityGroupListResponse _$ActivityGroupListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityGroupListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ActivityGroupListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ActivityGroupList.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ActivityGroupListResponseToJson(
  ActivityGroupListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
