// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_exam_dates_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachExamDatesResponse _$ExamCoachExamDatesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachExamDatesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ExamCoachExamDatesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ExamCoachExamDates.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachExamDatesResponseToJson(
  ExamCoachExamDatesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
