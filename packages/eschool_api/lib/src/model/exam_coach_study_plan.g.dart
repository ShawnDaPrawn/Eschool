// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_study_plan.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachStudyPlan _$ExamCoachStudyPlanFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachStudyPlan',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'group_id',
        'subject_id',
        'exam_on',
        'papers',
        'days',
      ],
    );
    final val = ExamCoachStudyPlan(
      id: $checkedConvert('id', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      examOn: $checkedConvert('exam_on', (v) => v as String),
      papers: $checkedConvert(
        'papers',
        (v) => (v as List<dynamic>)
            .map((e) => ExamCoachPlanPaper.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      days: $checkedConvert(
        'days',
        (v) => (v as List<dynamic>)
            .map((e) => ExamCoachStudyDay.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'groupId': 'group_id',
    'subjectId': 'subject_id',
    'examOn': 'exam_on',
  },
);

Map<String, dynamic> _$ExamCoachStudyPlanToJson(ExamCoachStudyPlan instance) =>
    <String, dynamic>{
      'id': instance.id,
      'learner_id': instance.learnerId,
      'group_id': instance.groupId,
      'subject_id': instance.subjectId,
      'exam_on': instance.examOn,
      'papers': instance.papers.map((e) => e.toJson()).toList(),
      'days': instance.days.map((e) => e.toJson()).toList(),
    };
