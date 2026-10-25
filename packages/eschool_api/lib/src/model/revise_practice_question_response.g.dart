// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'revise_practice_question_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RevisePracticeQuestionResponse _$RevisePracticeQuestionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RevisePracticeQuestionResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = RevisePracticeQuestionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeApproval.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$RevisePracticeQuestionResponseToJson(
  RevisePracticeQuestionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
