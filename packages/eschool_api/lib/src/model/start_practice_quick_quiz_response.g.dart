// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'start_practice_quick_quiz_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StartPracticeQuickQuizResponse _$StartPracticeQuickQuizResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StartPracticeQuickQuizResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = StartPracticeQuickQuizResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeTestSitting.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$StartPracticeQuickQuizResponseToJson(
  StartPracticeQuickQuizResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
