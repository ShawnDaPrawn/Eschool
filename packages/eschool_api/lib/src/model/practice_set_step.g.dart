// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_set_step.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeSetStep _$PracticeSetStepFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeSetStep', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'prompt', 'kind', 'options']);
      final val = PracticeSetStep(
        id: $checkedConvert('id', (v) => v as String),
        prompt: $checkedConvert('prompt', (v) => v as String),
        kind: $checkedConvert(
          'kind',
          (v) => $enumDecode(
            _$PracticeSetStepKindEnumEnumMap,
            v,
            unknownValue: PracticeSetStepKindEnum.unknownDefaultOpenApi,
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
      );
      return val;
    });

Map<String, dynamic> _$PracticeSetStepToJson(PracticeSetStep instance) =>
    <String, dynamic>{
      'id': instance.id,
      'prompt': instance.prompt,
      'kind': _$PracticeSetStepKindEnumEnumMap[instance.kind]!,
      'options': instance.options.map((e) => e.toJson()).toList(),
    };

const _$PracticeSetStepKindEnumEnumMap = {
  PracticeSetStepKindEnum.choice: 'choice',
  PracticeSetStepKindEnum.number: 'number',
  PracticeSetStepKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
