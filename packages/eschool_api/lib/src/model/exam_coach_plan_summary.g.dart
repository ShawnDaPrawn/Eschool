// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_plan_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPlanSummary _$ExamCoachPlanSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachPlanSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['id', 'group_id', 'subject_id', 'exam_on', 'papers'],
    );
    final val = ExamCoachPlanSummary(
      id: $checkedConvert('id', (v) => v as String),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      examOn: $checkedConvert('exam_on', (v) => v as String),
      papers: $checkedConvert(
        'papers',
        (v) => (v as List<dynamic>)
            .map((e) => ExamCoachPlanPaper.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'groupId': 'group_id',
    'subjectId': 'subject_id',
    'examOn': 'exam_on',
  },
);

Map<String, dynamic> _$ExamCoachPlanSummaryToJson(
  ExamCoachPlanSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'group_id': instance.groupId,
  'subject_id': instance.subjectId,
  'exam_on': instance.examOn,
  'papers': instance.papers.map((e) => e.toJson()).toList(),
};
