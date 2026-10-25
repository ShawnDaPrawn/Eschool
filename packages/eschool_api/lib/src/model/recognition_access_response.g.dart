// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_access_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionAccessResponse _$RecognitionAccessResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionAccessResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RecognitionAccessResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RecognitionAccess.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RecognitionAccessResponseToJson(
  RecognitionAccessResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
