// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_learner_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionLearnerPageResponse _$RecognitionLearnerPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionLearnerPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RecognitionLearnerPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RecognitionLearnerPage.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RecognitionLearnerPageResponseToJson(
  RecognitionLearnerPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
