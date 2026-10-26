// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_plan_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPlanList _$ExamCoachPlanListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ExamCoachPlanList', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['plans', 'page', 'has_more']);
      final val = ExamCoachPlanList(
        plans: $checkedConvert(
          'plans',
          (v) => (v as List<dynamic>)
              .map(
                (e) => ExamCoachPlanSummary.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        hasMore: $checkedConvert('has_more', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'hasMore': 'has_more'});

Map<String, dynamic> _$ExamCoachPlanListToJson(ExamCoachPlanList instance) =>
    <String, dynamic>{
      'plans': instance.plans.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'has_more': instance.hasMore,
    };
