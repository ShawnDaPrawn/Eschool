// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_question_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationQuestionInput _$ConsultationQuestionInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConsultationQuestionInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['kind', 'label', 'required']);
  final val = ConsultationQuestionInput(
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$ConsultationQuestionInputKindEnumEnumMap,
        v,
        unknownValue: ConsultationQuestionInputKindEnum.unknownDefaultOpenApi,
      ),
    ),
    label: $checkedConvert('label', (v) => v as String),
    required_: $checkedConvert('required', (v) => v as bool),
    maxChoices: $checkedConvert('max_choices', (v) => (v as num?)?.toInt()),
    options: $checkedConvert(
      'options',
      (v) => (v as List<dynamic>?)
          ?.map(
            (e) => ConsultationOptionInput.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'required_': 'required', 'maxChoices': 'max_choices'});

Map<String, dynamic> _$ConsultationQuestionInputToJson(
  ConsultationQuestionInput instance,
) => <String, dynamic>{
  'kind': _$ConsultationQuestionInputKindEnumEnumMap[instance.kind]!,
  'label': instance.label,
  'required': instance.required_,
  'max_choices': ?instance.maxChoices,
  'options': ?instance.options?.map((e) => e.toJson()).toList(),
};

const _$ConsultationQuestionInputKindEnumEnumMap = {
  ConsultationQuestionInputKindEnum.single: 'single',
  ConsultationQuestionInputKindEnum.multiple: 'multiple',
  ConsultationQuestionInputKindEnum.text: 'text',
  ConsultationQuestionInputKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
