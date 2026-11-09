// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_practice_quiz_topics_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetPracticeQuizTopicsResponse _$GetPracticeQuizTopicsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetPracticeQuizTopicsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetPracticeQuizTopicsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeQuizTopics.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetPracticeQuizTopicsResponseToJson(
  GetPracticeQuizTopicsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
