// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_learner_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestLearnerResult _$PracticeTestLearnerResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestLearnerResult',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'name',
        'state',
        'correct_count',
        'marked_count',
        'closed_by',
      ],
    );
    final val = PracticeTestLearnerResult(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$PracticeTestLearnerResultStateEnumEnumMap,
          v,
          unknownValue:
              PracticeTestLearnerResultStateEnum.unknownDefaultOpenApi,
        ),
      ),
      correctCount: $checkedConvert(
        'correct_count',
        (v) => (v as num?)?.toInt(),
      ),
      markedCount: $checkedConvert('marked_count', (v) => (v as num?)?.toInt()),
      closedBy: $checkedConvert(
        'closed_by',
        (v) => $enumDecodeNullable(
          _$PracticeTestLearnerResultClosedByEnumEnumMap,
          v,
          unknownValue:
              PracticeTestLearnerResultClosedByEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'correctCount': 'correct_count',
    'markedCount': 'marked_count',
    'closedBy': 'closed_by',
  },
);

Map<String, dynamic> _$PracticeTestLearnerResultToJson(
  PracticeTestLearnerResult instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'name': instance.name,
  'state': _$PracticeTestLearnerResultStateEnumEnumMap[instance.state]!,
  'correct_count': instance.correctCount,
  'marked_count': instance.markedCount,
  'closed_by':
      _$PracticeTestLearnerResultClosedByEnumEnumMap[instance.closedBy],
};

const _$PracticeTestLearnerResultStateEnumEnumMap = {
  PracticeTestLearnerResultStateEnum.notStarted: 'not_started',
  PracticeTestLearnerResultStateEnum.open: 'open',
  PracticeTestLearnerResultStateEnum.timeUp: 'time_up',
  PracticeTestLearnerResultStateEnum.handedIn: 'handed_in',
  PracticeTestLearnerResultStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$PracticeTestLearnerResultClosedByEnumEnumMap = {
  PracticeTestLearnerResultClosedByEnum.learner: 'learner',
  PracticeTestLearnerResultClosedByEnum.time: 'time',
  PracticeTestLearnerResultClosedByEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
