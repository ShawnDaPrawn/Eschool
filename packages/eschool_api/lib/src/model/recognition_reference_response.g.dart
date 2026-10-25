// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_reference_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionReferenceResponse _$RecognitionReferenceResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionReferenceResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RecognitionReferenceResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RecognitionReference.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RecognitionReferenceResponseToJson(
  RecognitionReferenceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
