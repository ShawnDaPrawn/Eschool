// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'task_insight_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TaskInsightSummary _$TaskInsightSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TaskInsightSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'group_id',
        'task_id',
        'canonical_task_id',
        'learner_count',
        'has_question_evidence',
        'questions',
      ],
    );
    final val = TaskInsightSummary(
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      taskId: $checkedConvert('task_id', (v) => (v as num).toInt()),
      canonicalTaskId: $checkedConvert(
        'canonical_task_id',
        (v) => (v as num).toInt(),
      ),
      learnerCount: $checkedConvert('learner_count', (v) => (v as num).toInt()),
      hasQuestionEvidence: $checkedConvert(
        'has_question_evidence',
        (v) => v as bool,
      ),
      questions: $checkedConvert(
        'questions',
        (v) => (v as List<dynamic>)
            .map((e) => TaskInsightQuestion.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'groupId': 'group_id',
    'taskId': 'task_id',
    'canonicalTaskId': 'canonical_task_id',
    'learnerCount': 'learner_count',
    'hasQuestionEvidence': 'has_question_evidence',
  },
);

Map<String, dynamic> _$TaskInsightSummaryToJson(TaskInsightSummary instance) =>
    <String, dynamic>{
      'group_id': instance.groupId,
      'task_id': instance.taskId,
      'canonical_task_id': instance.canonicalTaskId,
      'learner_count': instance.learnerCount,
      'has_question_evidence': instance.hasQuestionEvidence,
      'questions': instance.questions.map((e) => e.toJson()).toList(),
    };
