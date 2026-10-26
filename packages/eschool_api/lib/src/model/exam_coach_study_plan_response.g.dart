// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_study_plan_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachStudyPlanResponse _$ExamCoachStudyPlanResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachStudyPlanResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ExamCoachStudyPlanResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ExamCoachStudyPlan.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachStudyPlanResponseToJson(
  ExamCoachStudyPlanResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
