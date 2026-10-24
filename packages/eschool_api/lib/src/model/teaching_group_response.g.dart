// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_group_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingGroupResponse _$TeachingGroupResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingGroupResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = TeachingGroupResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => TeachingGroup.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$TeachingGroupResponseToJson(
  TeachingGroupResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
