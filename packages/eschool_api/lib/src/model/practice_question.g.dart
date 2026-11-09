// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeQuestion _$PracticeQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeQuestion', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['kind', 'prompt', 'explanation', 'options', 'answers'],
  );
  final val = PracticeQuestion(
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$PracticeQuestionKindEnumEnumMap,
        v,
        unknownValue: PracticeQuestionKindEnum.unknownDefaultOpenApi,
      ),
    ),
    prompt: $checkedConvert('prompt', (v) => v as String),
    explanation: $checkedConvert('explanation', (v) => v as String),
    options: $checkedConvert(
      'options',
      (v) => (v as List<dynamic>)
          .map(
            (e) => PracticeQuestionOption.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    answers: $checkedConvert(
      'answers',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
    steps: $checkedConvert(
      'steps',
      (v) => (v as List<dynamic>?)
          ?.map((e) => PracticeStep.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    diagram: $checkedConvert(
      'diagram',
      (v) => v == null
          ? null
          : PracticeDiagramReference.fromJson(v as Map<String, dynamic>),
    ),
    regions: $checkedConvert(
      'regions',
      (v) => (v as List<dynamic>?)
          ?.map(
            (e) => PracticeDiagramRegion.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    labels: $checkedConvert(
      'labels',
      (v) => (v as List<dynamic>?)
          ?.map(
            (e) => PracticeQuestionOption.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    mapping: $checkedConvert(
      'mapping',
      (v) =>
          (v as Map<String, dynamic>?)?.map((k, e) => MapEntry(k, e as String)),
    ),
    maxSeconds: $checkedConvert('max_seconds', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'maxSeconds': 'max_seconds'});

Map<String, dynamic> _$PracticeQuestionToJson(PracticeQuestion instance) =>
    <String, dynamic>{
      'kind': _$PracticeQuestionKindEnumEnumMap[instance.kind]!,
      'prompt': instance.prompt,
      'explanation': instance.explanation,
      'options': instance.options.map((e) => e.toJson()).toList(),
      'answers': instance.answers,
      'steps': ?instance.steps?.map((e) => e.toJson()).toList(),
      'diagram': ?instance.diagram?.toJson(),
      'regions': ?instance.regions?.map((e) => e.toJson()).toList(),
      'labels': ?instance.labels?.map((e) => e.toJson()).toList(),
      'mapping': ?instance.mapping,
      'max_seconds': ?instance.maxSeconds,
    };

const _$PracticeQuestionKindEnumEnumMap = {
  PracticeQuestionKindEnum.choice: 'choice',
  PracticeQuestionKindEnum.number: 'number',
  PracticeQuestionKindEnum.steps: 'steps',
  PracticeQuestionKindEnum.shortText: 'short_text',
  PracticeQuestionKindEnum.diagramLabel: 'diagram_label',
  PracticeQuestionKindEnum.audio: 'audio',
  PracticeQuestionKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
