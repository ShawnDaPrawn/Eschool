// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationQuestion _$ConsultationQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConsultationQuestion', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'id',
      'kind',
      'label',
      'required',
      'max_choices',
      'options',
    ],
  );
  final val = ConsultationQuestion(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$ConsultationQuestionKindEnumEnumMap,
        v,
        unknownValue: ConsultationQuestionKindEnum.unknownDefaultOpenApi,
      ),
    ),
    label: $checkedConvert('label', (v) => v as String),
    required_: $checkedConvert('required', (v) => v as bool),
    maxChoices: $checkedConvert('max_choices', (v) => (v as num).toInt()),
    options: $checkedConvert(
      'options',
      (v) => (v as List<dynamic>)
          .map((e) => ConsultationOption.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'required_': 'required', 'maxChoices': 'max_choices'});

Map<String, dynamic> _$ConsultationQuestionToJson(
  ConsultationQuestion instance,
) => <String, dynamic>{
  'id': instance.id,
  'kind': _$ConsultationQuestionKindEnumEnumMap[instance.kind]!,
  'label': instance.label,
  'required': instance.required_,
  'max_choices': instance.maxChoices,
  'options': instance.options.map((e) => e.toJson()).toList(),
};

const _$ConsultationQuestionKindEnumEnumMap = {
  ConsultationQuestionKindEnum.single: 'single',
  ConsultationQuestionKindEnum.multiple: 'multiple',
  ConsultationQuestionKindEnum.text: 'text',
  ConsultationQuestionKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
