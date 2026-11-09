// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reading_questions_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadingQuestionsResponse _$ReadingQuestionsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReadingQuestionsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReadingQuestionsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ReadingQuestions.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ReadingQuestionsResponseToJson(
  ReadingQuestionsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
