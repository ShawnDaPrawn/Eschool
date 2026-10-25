// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_teacher_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportTeacherListResponse _$SupportTeacherListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SupportTeacherListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = SupportTeacherListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => SupportTeacherList.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SupportTeacherListResponseToJson(
  SupportTeacherListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
