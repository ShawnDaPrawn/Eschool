// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_skill.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsSkill _$StrengthsSkillFromJson(Map<String, dynamic> json) =>
    $checkedCreate('StrengthsSkill', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['kind', 'label']);
      final val = StrengthsSkill(
        kind: $checkedConvert(
          'kind',
          (v) => $enumDecode(
            _$StrengthsSkillKindEnumEnumMap,
            v,
            unknownValue: StrengthsSkillKindEnum.unknownDefaultOpenApi,
          ),
        ),
        label: $checkedConvert('label', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$StrengthsSkillToJson(StrengthsSkill instance) =>
    <String, dynamic>{
      'kind': _$StrengthsSkillKindEnumEnumMap[instance.kind]!,
      'label': instance.label,
    };

const _$StrengthsSkillKindEnumEnumMap = {
  StrengthsSkillKindEnum.leading: 'leading',
  StrengthsSkillKindEnum.helping: 'helping',
  StrengthsSkillKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
