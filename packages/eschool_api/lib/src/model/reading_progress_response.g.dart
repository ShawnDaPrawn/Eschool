// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_progress_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingProgressResponse _$ReadingProgressResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReadingProgressResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReadingProgressResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ReadingProgressSaved.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ReadingProgressResponseToJson(
  ReadingProgressResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
