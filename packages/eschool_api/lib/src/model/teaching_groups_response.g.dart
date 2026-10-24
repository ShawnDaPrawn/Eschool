// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_groups_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingGroupsResponse _$TeachingGroupsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingGroupsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = TeachingGroupsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => TeachingGroupsResponseData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$TeachingGroupsResponseToJson(
  TeachingGroupsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
