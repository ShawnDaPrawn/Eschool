// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'task_insight_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TaskInsightQuestion _$TaskInsightQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TaskInsightQuestion',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'question_id',
        'number',
        'topic_keys',
        'max_mark',
        'evidence_count',
        'unknown_count',
        'missed_count',
        'average_mark',
      ],
    );
    final val = TaskInsightQuestion(
      questionId: $checkedConvert('question_id', (v) => (v as num).toInt()),
      number: $checkedConvert('number', (v) => v as String),
      topicKeys: $checkedConvert(
        'topic_keys',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
      maxMark: $checkedConvert('max_mark', (v) => v as String),
      evidenceCount: $checkedConvert(
        'evidence_count',
        (v) => (v as num).toInt(),
      ),
      unknownCount: $checkedConvert('unknown_count', (v) => (v as num).toInt()),
      missedCount: $checkedConvert('missed_count', (v) => (v as num).toInt()),
      averageMark: $checkedConvert('average_mark', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'questionId': 'question_id',
    'topicKeys': 'topic_keys',
    'maxMark': 'max_mark',
    'evidenceCount': 'evidence_count',
    'unknownCount': 'unknown_count',
    'missedCount': 'missed_count',
    'averageMark': 'average_mark',
  },
);

Map<String, dynamic> _$TaskInsightQuestionToJson(
  TaskInsightQuestion instance,
) => <String, dynamic>{
  'question_id': instance.questionId,
  'number': instance.number,
  'topic_keys': instance.topicKeys,
  'max_mark': instance.maxMark,
  'evidence_count': instance.evidenceCount,
  'unknown_count': instance.unknownCount,
  'missed_count': instance.missedCount,
  'average_mark': instance.averageMark,
};
