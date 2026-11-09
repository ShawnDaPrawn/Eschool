// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diagnostic_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiagnosticQuestion _$DiagnosticQuestionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiagnosticQuestion', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'position', 'kind', 'prompt', 'options'],
      );
      final val = DiagnosticQuestion(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        position: $checkedConvert('position', (v) => (v as num).toInt()),
        kind: $checkedConvert(
          'kind',
          (v) => $enumDecode(
            _$DiagnosticQuestionKindEnumEnumMap,
            v,
            unknownValue: DiagnosticQuestionKindEnum.unknownDefaultOpenApi,
          ),
        ),
        prompt: $checkedConvert('prompt', (v) => v as String),
        options: $checkedConvert(
          'options',
          (v) => (v as List<dynamic>)
              .map(
                (e) => DiagnosticQuestionOptionsInner.fromJson(
                  e as Map<String, dynamic>,
                ),
              )
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$DiagnosticQuestionToJson(DiagnosticQuestion instance) =>
    <String, dynamic>{
      'id': instance.id,
      'position': instance.position,
      'kind': _$DiagnosticQuestionKindEnumEnumMap[instance.kind]!,
      'prompt': instance.prompt,
      'options': instance.options.map((e) => e.toJson()).toList(),
    };

const _$DiagnosticQuestionKindEnumEnumMap = {
  DiagnosticQuestionKindEnum.choice: 'choice',
  DiagnosticQuestionKindEnum.number: 'number',
  DiagnosticQuestionKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
