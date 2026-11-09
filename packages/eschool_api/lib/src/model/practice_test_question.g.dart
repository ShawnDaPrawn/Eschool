// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestQuestion _$PracticeTestQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeTestQuestion',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'position',
        'topic_id',
        'kind',
        'prompt',
        'options',
        'answer',
      ],
    );
    final val = PracticeTestQuestion(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      position: $checkedConvert('position', (v) => (v as num).toInt()),
      topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$PracticeTestQuestionKindEnumEnumMap,
          v,
          unknownValue: PracticeTestQuestionKindEnum.unknownDefaultOpenApi,
        ),
      ),
      prompt: $checkedConvert('prompt', (v) => v as String),
      options: $checkedConvert(
        'options',
        (v) => (v as List<dynamic>)
            .map((e) => PracticeTestOption.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      answer: $checkedConvert('answer', (v) => v as String?),
      marking: $checkedConvert(
        'marking',
        (v) => $enumDecodeNullable(
          _$PracticeTestQuestionMarkingEnumEnumMap,
          v,
          unknownValue: PracticeTestQuestionMarkingEnum.unknownDefaultOpenApi,
        ),
      ),
      correct: $checkedConvert('correct', (v) => v as bool?),
      acceptedAnswers: $checkedConvert(
        'accepted_answers',
        (v) => (v as List<dynamic>?)?.map((e) => e as String).toList(),
      ),
      explanation: $checkedConvert('explanation', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'topicId': 'topic_id',
    'acceptedAnswers': 'accepted_answers',
  },
);

Map<String, dynamic> _$PracticeTestQuestionToJson(
  PracticeTestQuestion instance,
) => <String, dynamic>{
  'id': instance.id,
  'position': instance.position,
  'topic_id': instance.topicId,
  'kind': _$PracticeTestQuestionKindEnumEnumMap[instance.kind]!,
  'prompt': instance.prompt,
  'options': instance.options.map((e) => e.toJson()).toList(),
  'answer': instance.answer,
  'marking': ?_$PracticeTestQuestionMarkingEnumEnumMap[instance.marking],
  'correct': ?instance.correct,
  'accepted_answers': ?instance.acceptedAnswers,
  'explanation': ?instance.explanation,
};

const _$PracticeTestQuestionKindEnumEnumMap = {
  PracticeTestQuestionKindEnum.choice: 'choice',
  PracticeTestQuestionKindEnum.number: 'number',
  PracticeTestQuestionKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$PracticeTestQuestionMarkingEnumEnumMap = {
  PracticeTestQuestionMarkingEnum.auto: 'auto',
  PracticeTestQuestionMarkingEnum.awaitingMarker: 'awaiting_marker',
  PracticeTestQuestionMarkingEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
