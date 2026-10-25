// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'approve_practice_question_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ApprovePracticeQuestionResponse _$ApprovePracticeQuestionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ApprovePracticeQuestionResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ApprovePracticeQuestionResponse(
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

Map<String, dynamic> _$ApprovePracticeQuestionResponseToJson(
  ApprovePracticeQuestionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
