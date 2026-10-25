// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_owner_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskOwnerChoice _$RiskOwnerChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('RiskOwnerChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name', 'authority']);
      final val = RiskOwnerChoice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        authority: $checkedConvert(
          'authority',
          (v) => $enumDecode(
            _$RiskOwnerChoiceAuthorityEnumEnumMap,
            v,
            unknownValue: RiskOwnerChoiceAuthorityEnum.unknownDefaultOpenApi,
          ),
        ),
      );
      return val;
    });

Map<String, dynamic> _$RiskOwnerChoiceToJson(RiskOwnerChoice instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'authority': _$RiskOwnerChoiceAuthorityEnumEnumMap[instance.authority]!,
    };

const _$RiskOwnerChoiceAuthorityEnumEnumMap = {
  RiskOwnerChoiceAuthorityEnum.professional: 'professional',
  RiskOwnerChoiceAuthorityEnum.academic: 'academic',
  RiskOwnerChoiceAuthorityEnum.leadership: 'leadership',
  RiskOwnerChoiceAuthorityEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
