// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_plan_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumPlanWrite _$CurriculumPlanWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CurriculumPlanWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'subject_id',
        'grade_level_id',
        'session_year_id',
        'title',
        'rounding',
        'absent_policy',
        'topics',
        'assessments',
      ],
    );
    final val = CurriculumPlanWrite(
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
      revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
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
          _$CurriculumPlanWriteRoundingEnumEnumMap,
          v,
          unknownValue: CurriculumPlanWriteRoundingEnum.unknownDefaultOpenApi,
        ),
      ),
      absentPolicy: $checkedConvert(
        'absent_policy',
        (v) => $enumDecode(
          _$CurriculumPlanWriteAbsentPolicyEnumEnumMap,
          v,
          unknownValue:
              CurriculumPlanWriteAbsentPolicyEnum.unknownDefaultOpenApi,
        ),
      ),
      topics: $checkedConvert(
        'topics',
        (v) => (v as List<dynamic>)
            .map(
              (e) => CurriculumTopicWrite.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      assessments: $checkedConvert(
        'assessments',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  CurriculumAssessmentWrite.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
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
  },
);

Map<String, dynamic> _$CurriculumPlanWriteToJson(
  CurriculumPlanWrite instance,
) => <String, dynamic>{
  'subject_id': instance.subjectId,
  'grade_level_id': instance.gradeLevelId,
  'session_year_id': instance.sessionYearId,
  'title': instance.title,
  'revision': ?instance.revision,
  'standard_id': ?instance.standardId,
  'annual_components': ?instance.annualComponents,
  'rounding': _$CurriculumPlanWriteRoundingEnumEnumMap[instance.rounding]!,
  'absent_policy':
      _$CurriculumPlanWriteAbsentPolicyEnumEnumMap[instance.absentPolicy]!,
  'topics': instance.topics.map((e) => e.toJson()).toList(),
  'assessments': instance.assessments.map((e) => e.toJson()).toList(),
};

const _$CurriculumPlanWriteRoundingEnumEnumMap = {
  CurriculumPlanWriteRoundingEnum.halfUp: 'half_up',
  CurriculumPlanWriteRoundingEnum.halfEven: 'half_even',
  CurriculumPlanWriteRoundingEnum.floor: 'floor',
  CurriculumPlanWriteRoundingEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$CurriculumPlanWriteAbsentPolicyEnumEnumMap = {
  CurriculumPlanWriteAbsentPolicyEnum.zero: 'zero',
  CurriculumPlanWriteAbsentPolicyEnum.incomplete: 'incomplete',
  CurriculumPlanWriteAbsentPolicyEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
