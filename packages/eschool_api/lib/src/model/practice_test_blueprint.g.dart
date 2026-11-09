// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_blueprint.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestBlueprint _$PracticeTestBlueprintFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestBlueprint',
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
      ],
    );
    final val = PracticeTestBlueprint(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$PracticeTestBlueprintKindEnumEnumMap,
          v,
          unknownValue: PracticeTestBlueprintKindEnum.unknownDefaultOpenApi,
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
          _$PracticeTestBlueprintWeightingEnumEnumMap,
          v,
          unknownValue:
              PracticeTestBlueprintWeightingEnum.unknownDefaultOpenApi,
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

Map<String, dynamic> _$PracticeTestBlueprintToJson(
  PracticeTestBlueprint instance,
) => <String, dynamic>{
  'id': instance.id,
  'kind': _$PracticeTestBlueprintKindEnumEnumMap[instance.kind]!,
  'group_id': instance.groupId,
  'title': instance.title,
  'question_count': instance.questionCount,
  'time_limit_minutes': instance.timeLimitMinutes,
  'opens_at': instance.opensAt?.toIso8601String(),
  'closes_at': instance.closesAt?.toIso8601String(),
  'weighting': _$PracticeTestBlueprintWeightingEnumEnumMap[instance.weighting]!,
  'topic_shares': instance.topicShares.map((e) => e.toJson()).toList(),
  'active': instance.active,
  'revision': instance.revision,
};

const _$PracticeTestBlueprintKindEnumEnumMap = {
  PracticeTestBlueprintKindEnum.quickQuiz: 'quick_quiz',
  PracticeTestBlueprintKindEnum.cycleTest: 'cycle_test',
  PracticeTestBlueprintKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$PracticeTestBlueprintWeightingEnumEnumMap = {
  PracticeTestBlueprintWeightingEnum.even: 'even',
  PracticeTestBlueprintWeightingEnum.pastPapers: 'past_papers',
  PracticeTestBlueprintWeightingEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
