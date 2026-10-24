// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_plans_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumPlansResponseData _$CurriculumPlansResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CurriculumPlansResponseData', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['plans', 'current_page', 'last_page', 'total'],
  );
  final val = CurriculumPlansResponseData(
    plans: $checkedConvert(
      'plans',
      (v) => (v as List<dynamic>)
          .map((e) => CurriculumPlanSummary.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    total: $checkedConvert('total', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'currentPage': 'current_page', 'lastPage': 'last_page'});

Map<String, dynamic> _$CurriculumPlansResponseDataToJson(
  CurriculumPlansResponseData instance,
) => <String, dynamic>{
  'plans': instance.plans.map((e) => e.toJson()).toList(),
  'current_page': instance.currentPage,
  'last_page': instance.lastPage,
  'total': instance.total,
};
