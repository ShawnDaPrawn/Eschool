// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_plan_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumPlanResponse _$CurriculumPlanResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CurriculumPlanResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CurriculumPlanResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CurriculumPlan.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CurriculumPlanResponseToJson(
  CurriculumPlanResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
