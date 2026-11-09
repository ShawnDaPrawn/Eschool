// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_step.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeStep _$PracticeStepFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeStep', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['prompt', 'kind', 'options', 'answers'],
      );
      final val = PracticeStep(
        id: $checkedConvert('id', (v) => v as String?),
        prompt: $checkedConvert('prompt', (v) => v as String),
        kind: $checkedConvert(
          'kind',
          (v) => $enumDecode(
            _$PracticeStepKindEnumEnumMap,
            v,
            unknownValue: PracticeStepKindEnum.unknownDefaultOpenApi,
          ),
        ),
        options: $checkedConvert(
          'options',
          (v) => (v as List<dynamic>)
              .map(
                (e) =>
                    PracticeQuestionOption.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        answers: $checkedConvert(
          'answers',
          (v) => (v as List<dynamic>).map((e) => e as String).toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$PracticeStepToJson(PracticeStep instance) =>
    <String, dynamic>{
      'id': ?instance.id,
      'prompt': instance.prompt,
      'kind': _$PracticeStepKindEnumEnumMap[instance.kind]!,
      'options': instance.options.map((e) => e.toJson()).toList(),
      'answers': instance.answers,
    };

const _$PracticeStepKindEnumEnumMap = {
  PracticeStepKindEnum.choice: 'choice',
  PracticeStepKindEnum.number: 'number',
  PracticeStepKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
