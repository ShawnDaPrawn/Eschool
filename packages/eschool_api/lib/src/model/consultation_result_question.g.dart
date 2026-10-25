// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_result_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationResultQuestion _$ConsultationResultQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConsultationResultQuestion', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'id',
      'kind',
      'label',
      'required',
      'max_choices',
      'options',
      'answered',
      'counts',
    ],
  );
  final val = ConsultationResultQuestion(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$ConsultationResultQuestionKindEnumEnumMap,
        v,
        unknownValue: ConsultationResultQuestionKindEnum.unknownDefaultOpenApi,
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
    answered: $checkedConvert('answered', (v) => (v as num).toInt()),
    counts: $checkedConvert(
      'counts',
      (v) => (v as List<dynamic>)
          .map(
            (e) => ConsultationOptionCount.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'required_': 'required', 'maxChoices': 'max_choices'});

Map<String, dynamic> _$ConsultationResultQuestionToJson(
  ConsultationResultQuestion instance,
) => <String, dynamic>{
  'id': instance.id,
  'kind': _$ConsultationResultQuestionKindEnumEnumMap[instance.kind]!,
  'label': instance.label,
  'required': instance.required_,
  'max_choices': instance.maxChoices,
  'options': instance.options.map((e) => e.toJson()).toList(),
  'answered': instance.answered,
  'counts': instance.counts.map((e) => e.toJson()).toList(),
};

const _$ConsultationResultQuestionKindEnumEnumMap = {
  ConsultationResultQuestionKindEnum.single: 'single',
  ConsultationResultQuestionKindEnum.multiple: 'multiple',
  ConsultationResultQuestionKindEnum.text: 'text',
  ConsultationResultQuestionKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
