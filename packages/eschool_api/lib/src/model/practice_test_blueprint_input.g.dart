// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_blueprint_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestBlueprintInput _$PracticeTestBlueprintInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestBlueprintInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'title',
        'topic_ids',
        'question_count',
        'time_limit_minutes',
        'opens_at',
        'closes_at',
        'weighting',
      ],
    );
    final val = PracticeTestBlueprintInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      title: $checkedConvert('title', (v) => v as String),
      topicIds: $checkedConvert(
        'topic_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
      ),
      questionCount: $checkedConvert(
        'question_count',
        (v) => (v as num).toInt(),
      ),
      timeLimitMinutes: $checkedConvert(
        'time_limit_minutes',
        (v) => (v as num).toInt(),
      ),
      opensAt: $checkedConvert('opens_at', (v) => DateTime.parse(v as String)),
      closesAt: $checkedConvert(
        'closes_at',
        (v) => DateTime.parse(v as String),
      ),
      weighting: $checkedConvert(
        'weighting',
        (v) => $enumDecode(
          _$PracticeTestBlueprintInputWeightingEnumEnumMap,
          v,
          unknownValue:
              PracticeTestBlueprintInputWeightingEnum.unknownDefaultOpenApi,
        ),
      ),
      paperVersionIds: $checkedConvert(
        'paper_version_ids',
        (v) => (v as List<dynamic>?)?.map((e) => e as String).toSet(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'topicIds': 'topic_ids',
    'questionCount': 'question_count',
    'timeLimitMinutes': 'time_limit_minutes',
    'opensAt': 'opens_at',
    'closesAt': 'closes_at',
    'paperVersionIds': 'paper_version_ids',
  },
);

Map<String, dynamic> _$PracticeTestBlueprintInputToJson(
  PracticeTestBlueprintInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'title': instance.title,
  'topic_ids': instance.topicIds.toList(),
  'question_count': instance.questionCount,
  'time_limit_minutes': instance.timeLimitMinutes,
  'opens_at': instance.opensAt.toIso8601String(),
  'closes_at': instance.closesAt.toIso8601String(),
  'weighting':
      _$PracticeTestBlueprintInputWeightingEnumEnumMap[instance.weighting]!,
  'paper_version_ids': ?instance.paperVersionIds?.toList(),
};

const _$PracticeTestBlueprintInputWeightingEnumEnumMap = {
  PracticeTestBlueprintInputWeightingEnum.even: 'even',
  PracticeTestBlueprintInputWeightingEnum.pastPapers: 'past_papers',
  PracticeTestBlueprintInputWeightingEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
