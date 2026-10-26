// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_checkpoint_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorCheckpointDetail _$TutorCheckpointDetailFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'TutorCheckpointDetail',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'id',
        'session_id',
        'learner_id',
        'topic_id',
        'operation_id',
        'answer_operation_id',
        'state',
        'question',
        'correct',
        'explanation',
        'answered_at',
        'verified_by',
      ],
    );
    final val = TutorCheckpointDetail(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      id: $checkedConvert('id', (v) => v as String),
      sessionId: $checkedConvert('session_id', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      answerOperationId: $checkedConvert(
        'answer_operation_id',
        (v) => v as String?,
      ),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$TutorCheckpointDetailStateEnumEnumMap,
          v,
          unknownValue: TutorCheckpointDetailStateEnum.unknownDefaultOpenApi,
        ),
      ),
      question: $checkedConvert(
        'question',
        (v) => TutorCheckpointQuestion.fromJson(v as Map<String, dynamic>),
      ),
      correct: $checkedConvert('correct', (v) => v as bool?),
      explanation: $checkedConvert('explanation', (v) => v as String?),
      answeredAt: $checkedConvert(
        'answered_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      verifiedBy: $checkedConvert('verified_by', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'sessionId': 'session_id',
    'learnerId': 'learner_id',
    'topicId': 'topic_id',
    'operationId': 'operation_id',
    'answerOperationId': 'answer_operation_id',
    'answeredAt': 'answered_at',
    'verifiedBy': 'verified_by',
  },
);

Map<String, dynamic> _$TutorCheckpointDetailToJson(
  TutorCheckpointDetail instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'id': instance.id,
  'session_id': instance.sessionId,
  'learner_id': instance.learnerId,
  'topic_id': instance.topicId,
  'operation_id': instance.operationId,
  'answer_operation_id': instance.answerOperationId,
  'state': _$TutorCheckpointDetailStateEnumEnumMap[instance.state]!,
  'question': instance.question.toJson(),
  'correct': instance.correct,
  'explanation': instance.explanation,
  'answered_at': instance.answeredAt?.toIso8601String(),
  'verified_by': instance.verifiedBy,
};

const _$TutorCheckpointDetailStateEnumEnumMap = {
  TutorCheckpointDetailStateEnum.ready: 'ready',
  TutorCheckpointDetailStateEnum.answered: 'answered',
  TutorCheckpointDetailStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
