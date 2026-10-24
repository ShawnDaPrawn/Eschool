// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_assessment_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumAssessmentWrite _$CurriculumAssessmentWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CurriculumAssessmentWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['term_id', 'components', 'tasks']);
  final val = CurriculumAssessmentWrite(
    termId: $checkedConvert('term_id', (v) => (v as num).toInt()),
    components: $checkedConvert(
      'components',
      (v) => Map<String, int>.from(v as Map),
    ),
    tasks: $checkedConvert(
      'tasks',
      (v) => (v as List<dynamic>)
          .map((e) => CurriculumTaskWrite.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'termId': 'term_id'});

Map<String, dynamic> _$CurriculumAssessmentWriteToJson(
  CurriculumAssessmentWrite instance,
) => <String, dynamic>{
  'term_id': instance.termId,
  'components': instance.components,
  'tasks': instance.tasks.map((e) => e.toJson()).toList(),
};
