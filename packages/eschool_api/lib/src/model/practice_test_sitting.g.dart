// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_sitting.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestSitting _$PracticeTestSittingFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestSitting',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'group_id',
        'blueprint_id',
        'kind',
        'title',
        'state',
        'server_time',
        'started_at',
        'deadline_at',
        'seconds_left',
        'time_limit_seconds',
        'submitted_at',
        'closed_by',
        'questions',
        'result',
      ],
    );
    final val = PracticeTestSitting(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      blueprintId: $checkedConvert('blueprint_id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$PracticeTestSittingKindEnumEnumMap,
          v,
          unknownValue: PracticeTestSittingKindEnum.unknownDefaultOpenApi,
        ),
      ),
      title: $checkedConvert('title', (v) => v as String?),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$PracticeTestSittingStateEnumEnumMap,
          v,
          unknownValue: PracticeTestSittingStateEnum.unknownDefaultOpenApi,
        ),
      ),
      serverTime: $checkedConvert(
        'server_time',
        (v) => DateTime.parse(v as String),
      ),
      startedAt: $checkedConvert(
        'started_at',
        (v) => DateTime.parse(v as String),
      ),
      deadlineAt: $checkedConvert(
        'deadline_at',
        (v) => DateTime.parse(v as String),
      ),
      secondsLeft: $checkedConvert('seconds_left', (v) => (v as num).toInt()),
      timeLimitSeconds: $checkedConvert(
        'time_limit_seconds',
        (v) => (v as num).toInt(),
      ),
      submittedAt: $checkedConvert(
        'submitted_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      closedBy: $checkedConvert(
        'closed_by',
        (v) => $enumDecodeNullable(
          _$PracticeTestSittingClosedByEnumEnumMap,
          v,
          unknownValue: PracticeTestSittingClosedByEnum.unknownDefaultOpenApi,
        ),
      ),
      questions: $checkedConvert(
        'questions',
        (v) => (v as List<dynamic>)
            .map(
              (e) => PracticeTestQuestion.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      result: $checkedConvert(
        'result',
        (v) => v == null
            ? null
            : PracticeTestResult.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'groupId': 'group_id',
    'blueprintId': 'blueprint_id',
    'serverTime': 'server_time',
    'startedAt': 'started_at',
    'deadlineAt': 'deadline_at',
    'secondsLeft': 'seconds_left',
    'timeLimitSeconds': 'time_limit_seconds',
    'submittedAt': 'submitted_at',
    'closedBy': 'closed_by',
  },
);

Map<String, dynamic> _$PracticeTestSittingToJson(
  PracticeTestSitting instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'group_id': instance.groupId,
  'blueprint_id': instance.blueprintId,
  'kind': _$PracticeTestSittingKindEnumEnumMap[instance.kind]!,
  'title': instance.title,
  'state': _$PracticeTestSittingStateEnumEnumMap[instance.state]!,
  'server_time': instance.serverTime.toIso8601String(),
  'started_at': instance.startedAt.toIso8601String(),
  'deadline_at': instance.deadlineAt.toIso8601String(),
  'seconds_left': instance.secondsLeft,
  'time_limit_seconds': instance.timeLimitSeconds,
  'submitted_at': instance.submittedAt?.toIso8601String(),
  'closed_by': _$PracticeTestSittingClosedByEnumEnumMap[instance.closedBy],
  'questions': instance.questions.map((e) => e.toJson()).toList(),
  'result': instance.result?.toJson(),
};

const _$PracticeTestSittingKindEnumEnumMap = {
  PracticeTestSittingKindEnum.quickQuiz: 'quick_quiz',
  PracticeTestSittingKindEnum.cycleTest: 'cycle_test',
  PracticeTestSittingKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$PracticeTestSittingStateEnumEnumMap = {
  PracticeTestSittingStateEnum.open: 'open',
  PracticeTestSittingStateEnum.timeUp: 'time_up',
  PracticeTestSittingStateEnum.handedIn: 'handed_in',
  PracticeTestSittingStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$PracticeTestSittingClosedByEnumEnumMap = {
  PracticeTestSittingClosedByEnum.learner: 'learner',
  PracticeTestSittingClosedByEnum.time: 'time',
  PracticeTestSittingClosedByEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
