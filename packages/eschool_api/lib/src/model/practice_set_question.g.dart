// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_set_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeSetQuestion _$PracticeSetQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeSetQuestion', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['kind', 'prompt', 'options']);
  final val = PracticeSetQuestion(
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$PracticeSetQuestionKindEnumEnumMap,
        v,
        unknownValue: PracticeSetQuestionKindEnum.unknownDefaultOpenApi,
      ),
    ),
    prompt: $checkedConvert('prompt', (v) => v as String),
    options: $checkedConvert(
      'options',
      (v) => (v as List<dynamic>)
          .map(
            (e) => PracticeQuestionOption.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    steps: $checkedConvert(
      'steps',
      (v) => (v as List<dynamic>?)
          ?.map((e) => PracticeSetStep.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    diagram: $checkedConvert(
      'diagram',
      (v) => v == null
          ? null
          : PracticeSetQuestionDiagram.fromJson(v as Map<String, dynamic>),
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
    maxSeconds: $checkedConvert('max_seconds', (v) => (v as num?)?.toInt()),
    maxLength: $checkedConvert('max_length', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'maxSeconds': 'max_seconds', 'maxLength': 'max_length'});

Map<String, dynamic> _$PracticeSetQuestionToJson(
  PracticeSetQuestion instance,
) => <String, dynamic>{
  'kind': _$PracticeSetQuestionKindEnumEnumMap[instance.kind]!,
  'prompt': instance.prompt,
  'options': instance.options.map((e) => e.toJson()).toList(),
  'steps': ?instance.steps?.map((e) => e.toJson()).toList(),
  'diagram': ?instance.diagram?.toJson(),
  'regions': ?instance.regions?.map((e) => e.toJson()).toList(),
  'labels': ?instance.labels?.map((e) => e.toJson()).toList(),
  'max_seconds': ?instance.maxSeconds,
  'max_length': ?instance.maxLength,
};

const _$PracticeSetQuestionKindEnumEnumMap = {
  PracticeSetQuestionKindEnum.choice: 'choice',
  PracticeSetQuestionKindEnum.number: 'number',
  PracticeSetQuestionKindEnum.steps: 'steps',
  PracticeSetQuestionKindEnum.shortText: 'short_text',
  PracticeSetQuestionKindEnum.diagramLabel: 'diagram_label',
  PracticeSetQuestionKindEnum.audio: 'audio',
  PracticeSetQuestionKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
