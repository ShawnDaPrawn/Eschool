// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_plan.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumPlan _$CurriculumPlanFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CurriculumPlan',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'subject_id',
        'grade_level_id',
        'session_year_id',
        'title',
        'version',
        'revision',
        'state',
        'standard_id',
        'annual_components',
        'rounding',
        'absent_policy',
        'topics',
        'assessments',
        'source_plan_id',
        'standard_snapshot',
      ],
    );
    final val = CurriculumPlan(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      title: $checkedConvert('title', (v) => v as String),
      version: $checkedConvert('version', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$CurriculumPlanStateEnumEnumMap,
          v,
          unknownValue: CurriculumPlanStateEnum.unknownDefaultOpenApi,
        ),
      ),
      standardId: $checkedConvert('standard_id', (v) => (v as num?)?.toInt()),
      annualComponents: $checkedConvert(
        'annual_components',
        (v) => (v as Map<String, dynamic>?)?.map(
          (k, e) => MapEntry(k, (e as num).toInt()),
        ),
      ),
      rounding: $checkedConvert(
        'rounding',
        (v) => $enumDecode(
          _$CurriculumPlanRoundingEnumEnumMap,
          v,
          unknownValue: CurriculumPlanRoundingEnum.unknownDefaultOpenApi,
        ),
      ),
      absentPolicy: $checkedConvert(
        'absent_policy',
        (v) => $enumDecode(
          _$CurriculumPlanAbsentPolicyEnumEnumMap,
          v,
          unknownValue: CurriculumPlanAbsentPolicyEnum.unknownDefaultOpenApi,
        ),
      ),
      topics: $checkedConvert(
        'topics',
        (v) => (v as List<dynamic>)
            .map((e) => CurriculumTopic.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      assessments: $checkedConvert(
        'assessments',
        (v) => (v as List<dynamic>)
            .map(
              (e) => CurriculumAssessment.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      sourcePlanId: $checkedConvert(
        'source_plan_id',
        (v) => (v as num?)?.toInt(),
      ),
      standardSnapshot: $checkedConvert(
        'standard_snapshot',
        (v) => v == null
            ? null
            : SubjectStandard.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'subjectId': 'subject_id',
    'gradeLevelId': 'grade_level_id',
    'sessionYearId': 'session_year_id',
    'standardId': 'standard_id',
    'annualComponents': 'annual_components',
    'absentPolicy': 'absent_policy',
    'sourcePlanId': 'source_plan_id',
    'standardSnapshot': 'standard_snapshot',
  },
);

Map<String, dynamic> _$CurriculumPlanToJson(CurriculumPlan instance) =>
    <String, dynamic>{
      'id': instance.id,
      'subject_id': instance.subjectId,
      'grade_level_id': instance.gradeLevelId,
      'session_year_id': instance.sessionYearId,
      'title': instance.title,
      'version': instance.version,
      'revision': instance.revision,
      'state': _$CurriculumPlanStateEnumEnumMap[instance.state]!,
      'standard_id': instance.standardId,
      'annual_components': instance.annualComponents,
      'rounding': _$CurriculumPlanRoundingEnumEnumMap[instance.rounding]!,
      'absent_policy':
          _$CurriculumPlanAbsentPolicyEnumEnumMap[instance.absentPolicy]!,
      'topics': instance.topics.map((e) => e.toJson()).toList(),
      'assessments': instance.assessments.map((e) => e.toJson()).toList(),
      'source_plan_id': instance.sourcePlanId,
      'standard_snapshot': instance.standardSnapshot?.toJson(),
    };

const _$CurriculumPlanStateEnumEnumMap = {
  CurriculumPlanStateEnum.draft: 'draft',
  CurriculumPlanStateEnum.published: 'published',
  CurriculumPlanStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$CurriculumPlanRoundingEnumEnumMap = {
  CurriculumPlanRoundingEnum.halfUp: 'half_up',
  CurriculumPlanRoundingEnum.halfEven: 'half_even',
  CurriculumPlanRoundingEnum.floor: 'floor',
  CurriculumPlanRoundingEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$CurriculumPlanAbsentPolicyEnumEnumMap = {
  CurriculumPlanAbsentPolicyEnum.zero: 'zero',
  CurriculumPlanAbsentPolicyEnum.incomplete: 'incomplete',
  CurriculumPlanAbsentPolicyEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
