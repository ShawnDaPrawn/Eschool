// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_blueprint_list_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestBlueprintListItem _$PracticeTestBlueprintListItemFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestBlueprintListItem',
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
        'started',
        'handed_in',
      ],
    );
    final val = PracticeTestBlueprintListItem(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$PracticeTestBlueprintListItemKindEnumEnumMap,
          v,
          unknownValue:
              PracticeTestBlueprintListItemKindEnum.unknownDefaultOpenApi,
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
          _$PracticeTestBlueprintListItemWeightingEnumEnumMap,
          v,
          unknownValue:
              PracticeTestBlueprintListItemWeightingEnum.unknownDefaultOpenApi,
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
      started: $checkedConvert('started', (v) => (v as num).toInt()),
      handedIn: $checkedConvert('handed_in', (v) => (v as num).toInt()),
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
    'handedIn': 'handed_in',
  },
);

Map<String, dynamic> _$PracticeTestBlueprintListItemToJson(
  PracticeTestBlueprintListItem instance,
) => <String, dynamic>{
  'id': instance.id,
  'kind': _$PracticeTestBlueprintListItemKindEnumEnumMap[instance.kind]!,
  'group_id': instance.groupId,
  'title': instance.title,
  'question_count': instance.questionCount,
  'time_limit_minutes': instance.timeLimitMinutes,
  'opens_at': instance.opensAt?.toIso8601String(),
  'closes_at': instance.closesAt?.toIso8601String(),
  'weighting':
      _$PracticeTestBlueprintListItemWeightingEnumEnumMap[instance.weighting]!,
  'topic_shares': instance.topicShares.map((e) => e.toJson()).toList(),
  'active': instance.active,
  'revision': instance.revision,
  'started': instance.started,
  'handed_in': instance.handedIn,
};

const _$PracticeTestBlueprintListItemKindEnumEnumMap = {
  PracticeTestBlueprintListItemKindEnum.quickQuiz: 'quick_quiz',
  PracticeTestBlueprintListItemKindEnum.cycleTest: 'cycle_test',
  PracticeTestBlueprintListItemKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$PracticeTestBlueprintListItemWeightingEnumEnumMap = {
  PracticeTestBlueprintListItemWeightingEnum.even: 'even',
  PracticeTestBlueprintListItemWeightingEnum.pastPapers: 'past_papers',
  PracticeTestBlueprintListItemWeightingEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
