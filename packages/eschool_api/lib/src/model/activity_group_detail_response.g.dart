// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_group_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityGroupDetailResponse _$ActivityGroupDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityGroupDetailResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ActivityGroupDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ActivityGroupDetail.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ActivityGroupDetailResponseToJson(
  ActivityGroupDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
