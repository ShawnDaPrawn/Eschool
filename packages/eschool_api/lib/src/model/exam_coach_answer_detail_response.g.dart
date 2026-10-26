// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerDetailResponse _$ExamCoachAnswerDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachAnswerDetailResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ExamCoachAnswerDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ExamCoachAnswerDetail.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachAnswerDetailResponseToJson(
  ExamCoachAnswerDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
