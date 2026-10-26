// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_checkpoint_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorCheckpointQuestion _$TutorCheckpointQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorCheckpointQuestion', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['kind', 'prompt', 'options']);
  final val = TutorCheckpointQuestion(
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$TutorCheckpointQuestionKindEnumEnumMap,
        v,
        unknownValue: TutorCheckpointQuestionKindEnum.unknownDefaultOpenApi,
      ),
    ),
    prompt: $checkedConvert('prompt', (v) => v as String),
    options: $checkedConvert(
      'options',
      (v) => (v as List<dynamic>)
          .map((e) => TutorCheckpointOption.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$TutorCheckpointQuestionToJson(
  TutorCheckpointQuestion instance,
) => <String, dynamic>{
  'kind': _$TutorCheckpointQuestionKindEnumEnumMap[instance.kind]!,
  'prompt': instance.prompt,
  'options': instance.options.map((e) => e.toJson()).toList(),
};

const _$TutorCheckpointQuestionKindEnumEnumMap = {
  TutorCheckpointQuestionKindEnum.choice: 'choice',
  TutorCheckpointQuestionKindEnum.number: 'number',
  TutorCheckpointQuestionKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
