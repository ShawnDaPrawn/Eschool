// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_plan_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPlanListResponse _$ExamCoachPlanListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachPlanListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ExamCoachPlanListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ExamCoachPlanList.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachPlanListResponseToJson(
  ExamCoachPlanListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
