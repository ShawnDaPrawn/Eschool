// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_plan_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumPlanSummary _$CurriculumPlanSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CurriculumPlanSummary',
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
      ],
    );
    final val = CurriculumPlanSummary(
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
          _$CurriculumPlanSummaryStateEnumEnumMap,
          v,
          unknownValue: CurriculumPlanSummaryStateEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'subjectId': 'subject_id',
    'gradeLevelId': 'grade_level_id',
    'sessionYearId': 'session_year_id',
  },
);

Map<String, dynamic> _$CurriculumPlanSummaryToJson(
  CurriculumPlanSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'subject_id': instance.subjectId,
  'grade_level_id': instance.gradeLevelId,
  'session_year_id': instance.sessionYearId,
  'title': instance.title,
  'version': instance.version,
  'revision': instance.revision,
  'state': _$CurriculumPlanSummaryStateEnumEnumMap[instance.state]!,
};

const _$CurriculumPlanSummaryStateEnumEnumMap = {
  CurriculumPlanSummaryStateEnum.draft: 'draft',
  CurriculumPlanSummaryStateEnum.published: 'published',
  CurriculumPlanSummaryStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
