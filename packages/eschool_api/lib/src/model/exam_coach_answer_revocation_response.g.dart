// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_revocation_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerRevocationResponse _$ExamCoachAnswerRevocationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachAnswerRevocationResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ExamCoachAnswerRevocationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ExamCoachAnswerRevocation.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachAnswerRevocationResponseToJson(
  ExamCoachAnswerRevocationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
