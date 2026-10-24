// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_assessment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumAssessment _$CurriculumAssessmentFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CurriculumAssessment', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'id',
      'term_id',
      'components',
      'tasks',
      'state',
      'published_at',
    ],
  );
  final val = CurriculumAssessment(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    termId: $checkedConvert('term_id', (v) => (v as num).toInt()),
    components: $checkedConvert(
      'components',
      (v) => Map<String, int>.from(v as Map),
    ),
    tasks: $checkedConvert(
      'tasks',
      (v) => (v as List<dynamic>)
          .map((e) => CurriculumTask.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$CurriculumAssessmentStateEnumEnumMap,
        v,
        unknownValue: CurriculumAssessmentStateEnum.unknownDefaultOpenApi,
      ),
    ),
    publishedAt: $checkedConvert(
      'published_at',
      (v) => v == null ? null : DateTime.parse(v as String),
    ),
  );
  return val;
}, fieldKeyMap: const {'termId': 'term_id', 'publishedAt': 'published_at'});

Map<String, dynamic> _$CurriculumAssessmentToJson(
  CurriculumAssessment instance,
) => <String, dynamic>{
  'id': instance.id,
  'term_id': instance.termId,
  'components': instance.components,
  'tasks': instance.tasks.map((e) => e.toJson()).toList(),
  'state': _$CurriculumAssessmentStateEnumEnumMap[instance.state]!,
  'published_at': instance.publishedAt?.toIso8601String(),
};

const _$CurriculumAssessmentStateEnumEnumMap = {
  CurriculumAssessmentStateEnum.draft: 'draft',
  CurriculumAssessmentStateEnum.published: 'published',
  CurriculumAssessmentStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
