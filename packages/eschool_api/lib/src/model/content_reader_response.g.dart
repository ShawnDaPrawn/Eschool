// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_reader_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentReaderResponse _$ContentReaderResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ContentReaderResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = ContentReaderResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => ContentReader.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ContentReaderResponseToJson(
  ContentReaderResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
