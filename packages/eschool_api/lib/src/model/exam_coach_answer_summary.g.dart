// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerSummary _$ExamCoachAnswerSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachAnswerSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'operation_id',
        'state',
        'failure_code',
        'group_id',
        'paper_version_id',
        'question_number',
        'revision',
        'expires_at',
      ],
    );
    final val = ExamCoachAnswerSummary(
      id: $checkedConvert('id', (v) => v as String),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$ExamCoachAnswerSummaryStateEnumEnumMap,
          v,
          unknownValue: ExamCoachAnswerSummaryStateEnum.unknownDefaultOpenApi,
        ),
      ),
      failureCode: $checkedConvert('failure_code', (v) => v as String?),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      paperVersionId: $checkedConvert('paper_version_id', (v) => v as String),
      questionNumber: $checkedConvert('question_number', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'failureCode': 'failure_code',
    'groupId': 'group_id',
    'paperVersionId': 'paper_version_id',
    'questionNumber': 'question_number',
    'expiresAt': 'expires_at',
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
  },
);

Map<String, dynamic> _$ExamCoachAnswerSummaryToJson(
  ExamCoachAnswerSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'operation_id': instance.operationId,
  'state': _$ExamCoachAnswerSummaryStateEnumEnumMap[instance.state]!,
  'failure_code': instance.failureCode,
  'group_id': instance.groupId,
  'paper_version_id': instance.paperVersionId,
  'question_number': instance.questionNumber,
  'revision': instance.revision,
  'expires_at': instance.expiresAt.toIso8601String(),
  'learner_id': ?instance.learnerId,
  'learner_name': ?instance.learnerName,
};

const _$ExamCoachAnswerSummaryStateEnumEnumMap = {
  ExamCoachAnswerSummaryStateEnum.prepared: 'prepared',
  ExamCoachAnswerSummaryStateEnum.pending: 'pending',
  ExamCoachAnswerSummaryStateEnum.completed: 'completed',
  ExamCoachAnswerSummaryStateEnum.failed: 'failed',
  ExamCoachAnswerSummaryStateEnum.quarantined: 'quarantined',
  ExamCoachAnswerSummaryStateEnum.purged: 'purged',
  ExamCoachAnswerSummaryStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
