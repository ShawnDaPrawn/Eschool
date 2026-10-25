// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeQuestion _$PracticeQuestionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeQuestion', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'kind',
          'prompt',
          'explanation',
          'options',
          'answers',
        ],
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

Map<String, dynamic> _$PracticeQuestionToJson(PracticeQuestion instance) =>
    <String, dynamic>{
      'kind': _$PracticeQuestionKindEnumEnumMap[instance.kind]!,
      'prompt': instance.prompt,
      'explanation': instance.explanation,
      'options': instance.options.map((e) => e.toJson()).toList(),
      'answers': instance.answers,
    };

const _$PracticeQuestionKindEnumEnumMap = {
  PracticeQuestionKindEnum.choice: 'choice',
  PracticeQuestionKindEnum.number: 'number',
  PracticeQuestionKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
