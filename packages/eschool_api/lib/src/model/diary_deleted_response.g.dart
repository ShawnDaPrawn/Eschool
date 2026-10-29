// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_deleted_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryDeletedResponse _$DiaryDeletedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DiaryDeletedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = DiaryDeletedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => DiaryDeleted.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$DiaryDeletedResponseToJson(
  DiaryDeletedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
