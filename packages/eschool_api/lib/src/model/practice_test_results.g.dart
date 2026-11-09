// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_results.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestResults _$PracticeTestResultsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestResults',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'kind',
        'group_id',
        'title',
        'question_count',
        'time_limit_minutes',
        'opens_at',
        'closes_at',
        'weighting',
        'topic_shares',
        'active',
        'revision',
        'learners',
        'topics',
      ],
    );
    final val = PracticeTestResults(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$PracticeTestResultsKindEnumEnumMap,
          v,
          unknownValue: PracticeTestResultsKindEnum.unknownDefaultOpenApi,
        ),
      ),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      questionCount: $checkedConvert(
        'question_count',
        (v) => (v as num).toInt(),
      ),
      timeLimitMinutes: $checkedConvert(
        'time_limit_minutes',
        (v) => (v as num).toInt(),
      ),
      opensAt: $checkedConvert(
        'opens_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      closesAt: $checkedConvert(
        'closes_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      weighting: $checkedConvert(
        'weighting',
        (v) => $enumDecode(
          _$PracticeTestResultsWeightingEnumEnumMap,
          v,
          unknownValue: PracticeTestResultsWeightingEnum.unknownDefaultOpenApi,
        ),
      ),
      topicShares: $checkedConvert(
        'topic_shares',
        (v) => (v as List<dynamic>)
            .map(
              (e) => PracticeTestTopicShare.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      active: $checkedConvert('active', (v) => v as bool),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      learners: $checkedConvert(
        'learners',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  PracticeTestLearnerResult.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      topics: $checkedConvert(
        'topics',
        (v) => (v as List<dynamic>)
            .map(
              (e) => PracticeTestGroupTopic.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'groupId': 'group_id',
    'questionCount': 'question_count',
    'timeLimitMinutes': 'time_limit_minutes',
    'opensAt': 'opens_at',
    'closesAt': 'closes_at',
    'topicShares': 'topic_shares',
  },
);

Map<String, dynamic> _$PracticeTestResultsToJson(
  PracticeTestResults instance,
) => <String, dynamic>{
  'id': instance.id,
  'kind': _$PracticeTestResultsKindEnumEnumMap[instance.kind]!,
  'group_id': instance.groupId,
  'title': instance.title,
  'question_count': instance.questionCount,
  'time_limit_minutes': instance.timeLimitMinutes,
  'opens_at': instance.opensAt?.toIso8601String(),
  'closes_at': instance.closesAt?.toIso8601String(),
  'weighting': _$PracticeTestResultsWeightingEnumEnumMap[instance.weighting]!,
  'topic_shares': instance.topicShares.map((e) => e.toJson()).toList(),
  'active': instance.active,
  'revision': instance.revision,
  'learners': instance.learners.map((e) => e.toJson()).toList(),
  'topics': instance.topics.map((e) => e.toJson()).toList(),
};

const _$PracticeTestResultsKindEnumEnumMap = {
  PracticeTestResultsKindEnum.quickQuiz: 'quick_quiz',
  PracticeTestResultsKindEnum.cycleTest: 'cycle_test',
  PracticeTestResultsKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$PracticeTestResultsWeightingEnumEnumMap = {
  PracticeTestResultsWeightingEnum.even: 'even',
  PracticeTestResultsWeightingEnum.pastPapers: 'past_papers',
  PracticeTestResultsWeightingEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
