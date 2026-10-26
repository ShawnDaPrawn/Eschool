// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerDetail _$ExamCoachAnswerDetailFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ExamCoachAnswerDetail',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'operation_id',
        'state',
        'failure_code',
        'response',
        'review',
      ],
    );
    final val = ExamCoachAnswerDetail(
      id: $checkedConvert('id', (v) => v as String),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$ExamCoachAnswerDetailStateEnumEnumMap,
          v,
          unknownValue: ExamCoachAnswerDetailStateEnum.unknownDefaultOpenApi,
        ),
      ),
      failureCode: $checkedConvert('failure_code', (v) => v as String?),
      groupId: $checkedConvert('group_id', (v) => (v as num?)?.toInt()),
      paperVersionId: $checkedConvert('paper_version_id', (v) => v as String?),
      questionNumber: $checkedConvert('question_number', (v) => v as String?),
      revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      working: $checkedConvert('working', (v) => v as String?),
      response: $checkedConvert(
        'response',
        (v) => v == null
            ? null
            : ExamCoachAnswerFeedback.fromJson(v as Map<String, dynamic>),
      ),
      review: $checkedConvert(
        'review',
        (v) => v == null
            ? null
            : ExamCoachAnswerReview.fromJson(v as Map<String, dynamic>),
      ),
      provisional: $checkedConvert('provisional', (v) => v as bool?),
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
  },
);

Map<String, dynamic> _$ExamCoachAnswerDetailToJson(
  ExamCoachAnswerDetail instance,
) => <String, dynamic>{
  'id': instance.id,
  'operation_id': instance.operationId,
  'state': _$ExamCoachAnswerDetailStateEnumEnumMap[instance.state]!,
  'failure_code': instance.failureCode,
  'group_id': ?instance.groupId,
  'paper_version_id': ?instance.paperVersionId,
  'question_number': ?instance.questionNumber,
  'revision': ?instance.revision,
  'expires_at': ?instance.expiresAt?.toIso8601String(),
  'working': ?instance.working,
  'response': instance.response?.toJson(),
  'review': instance.review?.toJson(),
  'provisional': ?instance.provisional,
};

const _$ExamCoachAnswerDetailStateEnumEnumMap = {
  ExamCoachAnswerDetailStateEnum.prepared: 'prepared',
  ExamCoachAnswerDetailStateEnum.pending: 'pending',
  ExamCoachAnswerDetailStateEnum.completed: 'completed',
  ExamCoachAnswerDetailStateEnum.failed: 'failed',
  ExamCoachAnswerDetailStateEnum.quarantined: 'quarantined',
  ExamCoachAnswerDetailStateEnum.purged: 'purged',
  ExamCoachAnswerDetailStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
